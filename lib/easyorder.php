<?php

namespace OpenSource\Order;

use Bitrix\Main\Context;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;

use \OpenSourceOrderComponent;

class EasyOrder extends OpenSourceOrderComponent
{
    /**
     * @param string $siteId
     * @return Basket
     */
    public function createVirtualEmptyBasket($siteId)
    {
        $basket = Basket::create($siteId);
        $basket->setFUserId(Fuser::getId());

        return $basket;
    }

    /**
     * @param string $siteId
     * @param Basket $basket
     * @param int $productId
     * @return Result
     *
     * @throws Exception
     */
    public function addProduct($siteId, $basket, int $productId)
    {
        return $basket
            ->createItem('catalog', $productId)
            ->setFields(array(
                'QUANTITY' => 1,
                'LID' => $siteId,
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
            ));
    }

    /**
     * @param int $personTypeId
     * @return Order
     * @throws Exception
     */
    public function createVirtualEasyOrder(int $personTypeId, $basket)
    {
        global $USER;

        if (!isset($this->getPersonTypes()[$personTypeId])) {
            throw new RuntimeException(Loc::getMessage('OPEN_SOURCE_ORDER_UNKNOWN_PERSON_TYPE'));
        }

        $siteId = Context::getCurrent()->getSite();
        $basketItems = $basket->getOrderableItems();

        $this->order = Order::create($siteId, $USER->GetID());
        $this->order->setPersonTypeId($personTypeId);
        $this->order->setBasket($basketItems);

        return $this->order;
    }
}
