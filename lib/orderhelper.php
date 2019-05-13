<?php

namespace OpenSource\Order;

use Bitrix\Sale\Delivery;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Shipment;
use Exception;

class OrderHelper
{
    /**
     * @param Order $order
     * @return Shipment|null
     * @throws Exception
     */
    public static function getFirstNonSystemShipment(Order $order)
    {
        /** @var Shipment $shipment */
        foreach ($order->getShipmentCollection() as $shipment) {
            if (!$shipment->isSystem()) {
                return $shipment;
            }
        }

        return null;
    }

    /**
     * @param Order $order
     * @return PaySystem\Service[]
     *
     * @throws Exception
     */
    public static function getAvailablePaySystems(Order $order): array
    {
        $payment = Payment::create($order->getPaymentCollection());
        $payment->setField('SUM', $order->getPrice());
        $payment->setField('CURRENCY', $order->getCurrency());

        $paySystemList = PaySystem\Manager::getListWithRestrictions($payment);
        foreach ($paySystemList as $key => $paySystem) {
            $paySystemList[$key] = new PaySystem\Service($paySystem);
        }

        return $paySystemList;
    }

    /**
     * @param Shipment $shipment
     * @param Delivery\Services\Base[] $deliveryObjects
     * @return null[]|CalculationResult[]
     *
     * @throws Exception
     */
    public static function calcDeliveries(Shipment $shipment, array $deliveryObjects): array
    {
        $calculatedDeliveries = [];

        $order = $shipment->getParentOrder();

        $deliveryId = $shipment->getDeliveryId();
        $deliveryPrice = $shipment->getField('BASE_PRICE_DELIVERY');

        foreach ($deliveryObjects as $obDelivery) {
            $shipment->setField('DELIVERY_ID', $obDelivery->getId());
            $calculationResult = $obDelivery->calculate($shipment);

            if ($calculationResult->isSuccess()) {
                $shipment->setBasePriceDelivery($calculationResult->getPrice());
                $arShowPrices = $order->getDiscount()
                    ->getShowPrices();

                $data = $calculationResult->getData();
                $data['DISCOUNT_DATA'] = $arShowPrices['DELIVERY'];
                $calculationResult->setData($data);
            }

            $calculatedDeliveries[$obDelivery->getId()] = $calculationResult;
        }

        //restore actual data
        $shipment->setField('DELIVERY_ID', $deliveryId);
        $shipment->setBasePriceDelivery($deliveryPrice);

        return $calculatedDeliveries;
    }

}