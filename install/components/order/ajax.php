<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\Request;
use Bitrix\Sale\Location\Search\Finder;
use OpenSource\Order\LocationHelper;
use Bitrix\Sale\Delivery;
use OpenSource\Order\OrderHelper;

class OpenSourceOrderAjaxController extends Controller
{
    /**
     * OpenSourceOrderAjaxController constructor.
     * @param Request|null $request
     * @throws \Bitrix\Main\LoaderException
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
            ]
        ];
    }

    /**
     * @param string $q
     * @param int $limit
     * @param array $excludeParts
     * @param string $order
     * @return array
     */
    public function searchLocationAction(
        string $q = '',
        int $limit = 5,
        array $excludeParts = [],
        string $order = 'desc'
    ): array {
        $foundLocations = [];

        if ($q !== '') {
            if ($limit > 50 || $limit < 1) {
                $limit = 50;
            }

            $result = Finder::find([
                'select' => [
                    'ID',
                    'CODE'
                ],
                'filter' => [
                    'PHRASE' => $q
                ],
                'limit' => $limit
            ]);

            while ($arLocation = $result->fetch()) {
                $foundLocations[] = LocationHelper::getDisplayByCode($arLocation['CODE'], $excludeParts, $order);
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
        int $person_type_id = 1,
        array $properties = [],
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

        $data = [];
        foreach (OrderHelper::calcDeliveries($shipment, $availableDeliveries) as $deliveryId => $calculationResult) {
            $obDelivery = $availableDeliveries[$deliveryId];

            $arDelivery = [
                'id' => $obDelivery->getId(),
                'name' => $obDelivery->getName(),
                'logo_path' => $obDelivery->getLogotipPath(),
                'period' => $calculationResult->getPeriodDescription(),
                'price' => $calculationResult->getPrice(),
                'price_display' => SaleFormatCurrency(
                    $calculationResult->getPrice(),
                    $componentClass->order->getCurrency()
                ),
            ];

            $data[$arDelivery['id']] = $arDelivery;
        }

        return $data;
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
                $data['result'] = true;
            } else {
                $this->errorCollection->add($saveResult->getErrors());
            }
        } else {
            $this->errorCollection->add($validationResult->getErrors());
        }

        return $data;
    }

}