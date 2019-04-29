<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\Controller;
use OpenSource\Order\LocationHelper;
use Bitrix\Sale\Delivery;
use OpenSource\Order\OrderHelper;

class OpenSourceOrderAjaxController extends Controller
{
    public function __construct(\Bitrix\Main\Request $request = null)
    {
        parent::__construct($request);

        \Bitrix\Main\Loader::includeModule('opensource.order');
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
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function searchLocationAction(string $q = '', int $limit = 5): array
    {
        $foundLocations = [];

        \Bitrix\Main\Loader::includeModule('sale');

        if ($q !== '') {
            if ($limit > 50) {
                $limit = 50;
            }

            $result = \Bitrix\Sale\Location\Search\Finder::find([
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
                $foundLocations[] = LocationHelper::getDisplayByCode($arLocation['CODE']);
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
     * @throws \Exception
     */
    public function calculateDeliveriesAction(
        int $person_type_id = 1,
        array $properties = [],
        array $delivery_ids = []
    ): array {
        CBitrixComponent::includeComponentClass('opensource.order');

        $componentClass = new OpenSourceOrderComponent();
        $componentClass->createVirtualOrder($person_type_id);
        $componentClass->setOrderProperties($properties);
        $shipment = $componentClass->createOrderShipment();

        $availableDeliveries = Delivery\Services\Manager::getRestrictedObjectsList($shipment);

        //filter available by argument if needed
        if (!empty($delivery_ids)) {
            $filteredDeliveries = [];
            foreach ($delivery_ids as $delivery_id) {
                if (isset($availableDeliveries[$delivery_id])) {
                    $filteredDeliveries[$delivery_id] = $availableDeliveries[$delivery_id];
                }
            }
            $availableDeliveries = $filteredDeliveries;
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
                'price_display' => \SaleFormatCurrency(
                    $calculationResult->getPrice(),
                    $componentClass->order->getCurrency()
                ),
            ];

            $data[$arDelivery['id']] = $arDelivery;
        }

        return $data;
    }

}