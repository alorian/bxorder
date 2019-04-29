<?php

namespace OpenSource\Order;

use Bitrix\Sale\Delivery;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Shipment;

class OrderHelper
{
    /**
     * @param Order $order
     * @return Shipment|null
     * @throws \Exception
     */
    public static function getFirstNonSystemShipment(Order $order): ?Shipment
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
     * @throws \Exception
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
     * @return null[]|\Bitrix\Sale\Delivery\CalculationResult[]
     *
     * @throws \Exception
     */
    public static function calcDeliveries(Shipment $shipment, array $deliveryObjects): array
    {
        $deliveryId = $shipment->getDeliveryId();

        $calculatedDeliveries = [];
        foreach ($deliveryObjects as $obDelivery) {
            $shipment->setField('DELIVERY_ID', $obDelivery->getId());
            $calculatedDeliveries[$obDelivery->getId()] = $obDelivery->calculate($shipment);
        }

        //restore actual delivery id
        $shipment->setField('DELIVERY_ID', $deliveryId);

        return $calculatedDeliveries;
    }

}