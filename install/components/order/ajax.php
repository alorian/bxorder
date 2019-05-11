<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Request;
use Bitrix\Sale\Location\Search\Finder;
use Bitrix\Sale\Location\TypeTable;
use OpenSource\Order\LocationHelper;
use Bitrix\Sale\Delivery;
use OpenSource\Order\OrderHelper;

class OpenSourceOrderAjaxController extends Controller
{
    /**
     * @param Request|null $request
     * @throws LoaderException
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        Loader::includeModule('sale');
        Loader::includeModule('opensource.order');
    }

    /**
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'searchLocation' => [
                'prefilters' => []
            ],
            'calculateDeliveries' => [
                'prefilters' => []
            ],
            'saveOrder' => [
                'prefilters' => []
            ]
        ];
    }

    /**
     * @param string $q
     * @param int $limit
     * @param string $typeCode
     * @param array $excludeParts
     * @param string $sortOrder
     * @return array
     * @throws Exception
     */
    public function searchLocationAction(
        string $q,
        int $limit = 5,
        string $typeCode = '',
        array $excludeParts = [],
        string $sortOrder = 'desc'
    ): array {
        $foundLocations = [];

        if ($q !== '') {
            if ($limit > 50 || $limit < 1) {
                $limit = 50;
            }

            //getting location type
            $typeId = null;
            if(!empty($typeCode)) {
                $arType = TypeTable::getList([
                    'select' => [
                        'ID',
                        'CODE'
                    ],
                    'filter' => [
                        '=CODE' => $typeCode
                    ]
                ])
                    ->fetch();

                if (!empty($arType)) {
                    $typeId = $arType['ID'];
                }
            }

            $result = Finder::find([
                'select' => [
                    'ID',
                    'CODE',
                ],
                'filter' => [
                    'PHRASE' => $q,
                    'TYPE_ID' => $typeId
                ],
                'limit' => $limit
            ]);

            while ($arLocation = $result->fetch()) {
                $foundLocations[] = LocationHelper::getDisplayByCode($arLocation['CODE'], $excludeParts, $sortOrder);
            }
        }

        return $foundLocations;
    }

    /**
     * @param int $person_type_id
     * @param array $properties
     * @param array $delivery_ids
     * @return array
     *
     * @throws Exception
     */
    public function calculateDeliveriesAction(
        int $person_type_id,
        array $properties,
        array $delivery_ids = []
    ): array {
        CBitrixComponent::includeComponentClass('opensource:order');

        $componentClass = new OpenSourceOrderComponent();
        $componentClass->createVirtualOrder($person_type_id);
        $componentClass->setOrderProperties($properties);
        $shipment = $componentClass->createOrderShipment();

        $availableDeliveries = Delivery\Services\Manager::getRestrictedObjectsList($shipment);

        //calc only needed if argument given
        if (!empty($delivery_ids)) {
            $availableDeliveries = array_intersect(
                $availableDeliveries,
                array_flip($delivery_ids)
            );
        }

        $calculatedDeliveries = [];
        foreach (OrderHelper::calcDeliveries($shipment, $availableDeliveries) as $deliveryId => $calculationResult) {
            $obDelivery = $availableDeliveries[$deliveryId];

            $arDelivery = [
                'id' => $obDelivery->getId(),
                'success' => $calculationResult->isSuccess(),
                'name' => $obDelivery->getName(),
                'logo_path' => $obDelivery->getLogotipPath(),
                'period' => $calculationResult->getPeriodDescription(),
                'base_price' => $calculationResult->getPrice(),
                'base_price_display' => SaleFormatCurrency(
                    $calculationResult->getPrice(),
                    $componentClass->order->getCurrency()
                ),
            ];

            $data = $calculationResult->getData();
            if (!empty($data['DISCOUNT_DATA'])) {
                $arDelivery['price'] = $data['DISCOUNT_DATA']['PRICE'];
                $arDelivery['price_display'] = SaleFormatCurrency(
                    $arDelivery['price'],
                    $componentClass->order->getCurrency()
                );
                $arDelivery['discount'] = $data['DISCOUNT_DATA']['DISCOUNT'];
                $arDelivery['discount_display'] = SaleFormatCurrency(
                    $arDelivery['discount'],
                    $componentClass->order->getCurrency()
                );
            } else {
                $arDelivery['price'] = $arDelivery['base_price'];
                $arDelivery['price_display'] = $arDelivery['base_price_display'];
                $arDelivery['discount'] = 0;
            }

            $arDelivery['errors'] = [];
            if (!$calculationResult->isSuccess()) {
                foreach ($calculationResult->getErrorMessages() as $message) {
                    $arDelivery['errors'][] = $message;
                }
            }

            $calculatedDeliveries[$arDelivery['id']] = $arDelivery;
        }

        return $calculatedDeliveries;
    }

    /**
     * @param int $person_type_id
     * @param array $properties
     * @param int $delivery_id
     * @param int $pay_system_id
     * @return array
     * @throws Exception
     */
    public function saveOrderAction(int $person_type_id, array $properties, int $delivery_id, int $pay_system_id): array
    {
        $data = [];

        CBitrixComponent::includeComponentClass('opensource:order');

        $componentClass = new OpenSourceOrderComponent();
        $componentClass->createVirtualOrder($person_type_id);
        $componentClass->setOrderProperties($properties);
        $componentClass->createOrderShipment($delivery_id);
        $componentClass->createOrderPayment($pay_system_id);

        $validationResult = $componentClass->validateOrder();
        if ($validationResult->isSuccess()) {
            $saveResult = $componentClass->order->save();
            if ($saveResult->isSuccess()) {
                $data['saved'] = true;
                $data['order_id'] = $saveResult->getId();
            } else {
                $this->errorCollection->add($saveResult->getErrors());
            }
        } else {
            $this->errorCollection->add($validationResult->getErrors());
        }

        return $data;
    }

}