<?php
/**
 * MAKING $arResult FROM SCRATCHES
 *
 * @var OpenSourceOrderComponent $component
 */

use Bitrix\Sale\BasketItem;
use Bitrix\Sale\BasketPropertyItem;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Order;
use Bitrix\Sale\PropertyValue;
use OpenSource\Order\LocationHelper;
use OpenSource\Order\OrderHelper;

$component = &$this->__component;
$order = $component->order;

if (!$order instanceof Order) {
    return;
}

/**
 * ORDER FIELDS
 */
$arResult = $order->getFieldValues();

/**
 * ORDER PROPERTIES
 */
$arResult['PROPERTIES'] = [];
foreach ($order->getPropertyCollection() as $prop) {
    /**
     * @var PropertyValue $prop
     */
    if ($prop->isUtil()) {
        continue;
    }

    $arProp['FORM_NAME'] = 'properties[' . $prop->getField('CODE') . ']';
    $arProp['FORM_LABEL'] = 'property_' . $prop->getField('CODE');

    $arProp['TYPE'] = $prop->getType();
    $arProp['NAME'] = $prop->getName();
    $arProp['VALUE'] = $prop->getValue();
    $arProp['IS_REQUIRED'] = $prop->isRequired();
    $arProp['ERRORS'] = $component->errorCollection->getAllErrorsByCode('PROPERTIES[' . $prop->getField('CODE') . ']');

    switch ($prop->getType()) {
        case 'LOCATION':
            if (!empty($arProp['VALUE'])) {
                $arProp['LOCATION_DATA'] = LocationHelper::getDisplayByCode($arProp['VALUE']);
            }
            break;

        case 'ENUM':
            $arProp['OPTIONS'] = $prop->getPropertyObject()
                ->getOptions();
            break;
    }

    $arResult['PROPERTIES'][$prop->getField('CODE')] = $arProp;
}


/**
 * DELIVERY
 */
$arResult['DELIVERY_ERRORS'] = [];
foreach ($component->errorCollection->getAllErrorsByCode('delivery') as $error) {
    $arResult['DELIVERY_ERRORS'][] = $error;
}

$arResult['DELIVERY_LIST'] = [];
$shipment = OrderHelper::getFirstNonSystemShipment($order);
if ($shipment !== null) {
    $availableDeliveries = Delivery\Services\Manager::getRestrictedObjectsList($shipment);
    $allDeliveryIDs = $order->getDeliveryIdList();
    $checkedDeliveryId = end($allDeliveryIDs);

    foreach (OrderHelper::calcDeliveries($shipment, $availableDeliveries) as $deliveryID => $calculationResult) {
        /**
         * @var Delivery\Services\Base $obDelivery
         */
        $obDelivery = $availableDeliveries[$deliveryID];

        $arDelivery = [];
        $arDelivery['ID'] = $obDelivery->getId();
        $arDelivery['NAME'] = $obDelivery->getName();
        $arDelivery['CHECKED'] = $checkedDeliveryId === $obDelivery->getId();
        $arDelivery['PRICE'] = $calculationResult->getPrice();
        $arDelivery['PRICE_DISPLAY'] = SaleFormatCurrency(
            $calculationResult->getDeliveryPrice(),
            $order->getCurrency()
        );

        $arResult['DELIVERY_LIST'][$deliveryID] = $arDelivery;
    }
}


/**
 * PAY SYSTEM
 */
$arResult['PAY_SYSTEM_ERRORS'] = [];
foreach ($component->errorCollection->getAllErrorsByCode('payment') as $error) {
    $arResult['PAY_SYSTEM_ERRORS'][] = $error;
}

$arResult['PAY_SYSTEM_LIST'] = [];
$availablePaySystem = OrderHelper::getAvailablePaySystems($order);
$checkedPaySystemId = 0;
if (!$order->getPaymentCollection()->isEmpty()) {
    $payment = $order->getPaymentCollection()->current();
    $checkedPaySystemId = $payment->getPaymentSystemId();
}
foreach ($availablePaySystem as $paySystem) {
    $arPaySystem = [];

    $arPaySystem['ID'] = $paySystem->getField('ID');
    $arPaySystem['NAME'] = $paySystem->getField('NAME');
    $arPaySystem['CHECKED'] = $arPaySystem['ID'] === $checkedPaySystemId;

    $arResult['PAY_SYSTEM_LIST'][$arPaySystem['ID']] = $arPaySystem;
}

/**
 * BASKET
 */
$arResult['BASKET'] = [];
foreach ($order->getBasket() as $basketItem) {
    /**
     * @var BasketItem $basketItem
     */
    $arBasketItem = [];
    $arBasketItem['ID'] = $basketItem->getId();
    $arBasketItem['NAME'] = $basketItem->getField('NAME');
    $arBasketItem['CURRENCY'] = $basketItem->getCurrency();

    $arBasketItem['PROPERTIES'] = [];
    foreach ($basketItem->getPropertyCollection() as $basketPropertyItem):
        /**
         * @var BasketPropertyItem $basketPropertyItem
         */
        $propCode = $basketPropertyItem->getField('CODE');
        if ($propCode !== 'CATALOG.XML_ID' && $propCode !== 'PRODUCT.XML_ID') {
            $arBasketItem['PROPERTIES'][] = [
                'NAME' => $basketPropertyItem->getField('NAME'),
                'VALUE' => $basketPropertyItem->getField('VALUE'),
            ];
        }
    endforeach;

    $arBasketItem['QUANTITY'] = $basketItem->getQuantity();
    $arBasketItem['QUANTITY_DISPLAY'] = $basketItem->getQuantity();
    $arBasketItem['QUANTITY_DISPLAY'] .= ' ' . $basketItem->getField('MEASURE_NAME');

    $arBasketItem['BASE_PRICE'] = $basketItem->getBasePrice();
    $arBasketItem['BASE_PRICE_DISPLAY'] = SaleFormatCurrency(
        $arBasketItem['BASE_PRICE'],
        $arBasketItem['CURRENCY']
    );

    $arBasketItem['PRICE'] = $basketItem->getPrice();
    $arBasketItem['PRICE_DISPLAY'] = SaleFormatCurrency(
        $arBasketItem['PRICE'],
        $arBasketItem['CURRENCY']
    );

    $arBasketItem['SUM'] = $basketItem->getPrice() * $basketItem->getQuantity();
    $arBasketItem['SUM_DISPLAY'] = SaleFormatCurrency(
        $arBasketItem['SUM'],
        $arBasketItem['CURRENCY']
    );

    $arResult['BASKET'][$arBasketItem['ID']] = $arBasketItem;
}

/**
 * ORDER TOTAL BASKET PRICES
 */
//Стоимость товаров без скидок
$arResult['PRODUCTS_BASE_PRICE'] = $order->getBasket()->getBasePrice();
$arResult['PRODUCTS_BASE_PRICE_DISPLAY'] = SaleFormatCurrency(
    $arResult['PRODUCTS_BASE_PRICE'],
    $arResult['CURRENCY']
);

//Стоимость товаров со скидами
$arResult['PRODUCTS_PRICE'] = $order->getBasket()->getPrice();
$arResult['PRODUCTS_PRICE_DISPLAY'] = SaleFormatCurrency(
    $arResult['PRODUCTS_PRICE'],
    $arResult['CURRENCY']
);

//Скидка на товары
$arResult['PRODUCTS_DISCOUNT'] = $arResult['PRODUCTS_BASE_PRICE'] - $arResult['PRODUCTS_PRICE'];
$arResult['PRODUCTS_DISCOUNT_DISPLAY'] = SaleFormatCurrency(
    $arResult['PRODUCTS_DISCOUNT'],
    $arResult['CURRENCY']
);

/**
 * ORDER TOTAL DELIVERY PRICES
 */
$arShowPrices = $order->getDiscount()
    ->getShowPrices();

//Стоимость доставки без скидок
$arResult['DELIVERY_BASE_PRICE'] = $arShowPrices['DELIVERY']['BASE_PRICE'] ?? 0;
$arResult['DELIVERY_BASE_PRICE_DISPLAY'] = SaleFormatCurrency(
    $arResult['DELIVERY_BASE_PRICE'],
    $arResult['CURRENCY']
);

//Стоимость доставки с учетом скидок
$arResult['DELIVERY_PRICE'] = $order->getDeliveryPrice();
$arResult['DELIVERY_PRICE_DISPLAY'] = SaleFormatCurrency(
    $arResult['DELIVERY_PRICE'],
    $arResult['CURRENCY']
);

//Скидка на доставку
$arResult['DELIVERY_DISCOUNT'] = $arShowPrices['DELIVERY']['DISCOUNT'] ?? 0;
$arResult['DELIVERY_DISCOUNT_DISPLAY'] = SaleFormatCurrency(
    $arResult['DELIVERY_PRICE'],
    $arResult['CURRENCY']
);

/**
 * ORDER TOTAL PRICES
 */
//Общая цена без скидок
$arResult['SUM_BASE'] = $arResult['PRODUCTS_BASE_PRICE'] + $arResult['DELIVERY_BASE_PRICE'];
$arResult['SUM_BASE_DISPLAY'] = SaleFormatCurrency(
    $arResult['SUM_BASE'],
    $arResult['CURRENCY']
);

//Общая скидка
$arResult['DISCOUNT_VALUE'] = $arResult['SUM_BASE'] - $order->getPrice();
$arResult['DISCOUNT_VALUE_DISPLAY'] = SaleFormatCurrency(
    $arResult['DISCOUNT_VALUE'],
    $arResult['CURRENCY']
);

//К оплате
$arResult['SUM'] = $order->getPrice();
$arResult['SUM_DISPLAY'] = SaleFormatCurrency(
    $arResult['SUM'],
    $arResult['CURRENCY']
);