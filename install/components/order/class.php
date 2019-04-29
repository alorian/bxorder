<?php

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PropertyValue;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentItem;
use Bitrix\Sale\ShipmentItemCollection;
use Bitrix\Sale\Delivery;
use OpenSource\Order\ErrorCollection;
use OpenSource\Order\OrderHelper;

class OpenSourceOrderComponent extends CBitrixComponent
{
    /**
     * @var Order
     */
    public $order;

    /**
     * @var ErrorCollection
     */
    public $errorCollection;

    protected $availablePaySystems;

    /**
     * CustomOrder constructor.
     * @param CBitrixComponent|null $component
     * @throws \Bitrix\Main\LoaderException
     */
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        Loader::includeModule('opensource.order');

        $this->errorCollection = new ErrorCollection();
    }

    public function onIncludeComponentLang(): void
    {
        Loc::loadLanguageFile(__FILE__);
    }

    public function onPrepareComponentParams($arParams = []): array
    {
        if (isset($arParams['DEFAULT_PERSON_TYPE_ID']) && (int)$arParams['DEFAULT_PERSON_TYPE_ID'] > 0) {
            $arParams['DEFAULT_PERSON_TYPE_ID'] = (int)$arParams['DEFAULT_PERSON_TYPE_ID'];
        } else {
            $arParams['DEFAULT_PERSON_TYPE_ID'] = 1;
        }

        if (isset($this->request['person_type_id']) && (int)$this->request['person_type_id'] > 0) {
            $arParams['PERSON_TYPE_ID'] = (int)$this->request['person_type_id'];
        } else {
            $arParams['PERSON_TYPE_ID'] = $arParams['DEFAULT_PERSON_TYPE_ID'];
        }

        if (isset($arParams['SAVE'])) {
            $arParams['SAVE'] = $arParams['SAVE'] === 'Y';
        } elseif (isset($this->request['save'])) {
            $arParams['SAVE'] = $this->request['save'] === 'y';
        } else {
            $arParams['SAVE'] = false;
        }

        return $arParams;
    }

    /**
     * @param int $personTypeId
     * @return Order
     * @throws \Exception
     */
    public function createVirtualOrder(int $personTypeId): Order
    {
        global $USER;

        $siteId = Context::getCurrent()
            ->getSite();

        $basketItems = \Bitrix\Sale\Basket::loadItemsForFUser(Fuser::getId(), $siteId)
            ->getOrderableItems();

        if (count($basketItems) === 0) {
            throw new \LengthException(Loc::getMessage('OPEN_SOURCE_ORDER_EMPTY_BASKET'));
        }

        $this->order = Order::create($siteId, $USER->GetID());
        $this->order->setPersonTypeId($personTypeId);
        $this->order->setBasket($basketItems);

        return $this->order;
    }

    /**
     * @param array $propertyValues
     * @throws \Exception
     */
    public function setOrderProperties(array $propertyValues): void
    {
        if (empty($propertyValues)) {
            return;
        }

        foreach ($this->order->getPropertyCollection() as $prop) {
            /**
             * @var \Bitrix\Sale\PropertyValue $prop
             */
            if ($prop->isUtil()) {
                continue;
            }

            $value = $propertyValues[$prop->getField('CODE')] ?? null;

            if (empty($value)) {
                $value = $prop->getProperty()['DEFAULT_VALUE'];
            }

            if (!empty($value)) {
                $prop->setValue($value);
            }
        }
    }

    /**
     * @param int $deliveryId
     * @return Shipment
     * @throws \Exception
     */
    public function createOrderShipment(int $deliveryId = 0): Shipment
    {
        /* @var $shipmentCollection \Bitrix\Sale\ShipmentCollection */
        $shipmentCollection = $this->order->getShipmentCollection();

        if ($deliveryId > 0) {
            $shipment = $shipmentCollection->createItem(
                Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId)
            );
        } else {
            $shipment = $shipmentCollection->createItem();
        }

        /** @var $shipmentItemCollection ShipmentItemCollection */
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipment->setField('CURRENCY', $this->order->getCurrency());

        foreach ($this->order->getBasket()->getOrderableItems() as $basketItem) {
            /**
             * @var $basketItem BasketItem
             * @var $shipmentItem ShipmentItem
             */
            $shipmentItem = $shipmentItemCollection->createItem($basketItem);
            $shipmentItem->setQuantity($basketItem->getQuantity());
        }

        return $shipment;
    }

    /**
     * @param int $paySystemId
     * @return Payment
     * @throws \Exception
     */
    public function createOrderPayment(int $paySystemId): Payment
    {
        $paymentCollection = $this->order->getPaymentCollection();
        $payment = $paymentCollection->createItem(
            Bitrix\Sale\PaySystem\Manager::getObjectById($paySystemId)
        );
        $payment->setField('SUM', $this->order->getPrice());
        $payment->setField('CURRENCY', $this->order->getCurrency());

        return $payment;
    }

    /**
     * @return Result
     *
     * @throws \Exception
     */
    public function validateProperties(): Result
    {
        $result = new Result();

        /**
         * PROPERTIES VALIDATION
         */
        foreach ($this->order->getPropertyCollection() as $prop) {
            /**
             * @var PropertyValue $prop
             */
            if ($prop->isRequired() && empty($prop->getValue())) {
                $result->addError(new Error(
                    Loc::getMessage('OPEN_SOURCE_ORDER_PROPERTY_REQUIRED', [
                        '#PROPERTY_NAME#' => $prop->getName()
                    ]),
                    'property_' . $prop->getField('CODE'),
                    [
                        'id' => $prop->getId(),
                        'code' => $prop->getField('CODE'),
                        'type' => 'required'
                    ]
                ));
            }

            if (!empty($prop->getValue())) {
                switch ($prop->getType()) {
                    case 'ENUM':
                        $selectedOptions = $prop->getValue();
                        if (!is_array($selectedOptions)) {
                            $selectedOptions = [$selectedOptions];
                        }
                        $property = $prop->getPropertyObject();
                        if ($property !== null) {
                            $availableOptions = $property->getOptions();
                            foreach ($selectedOptions as $option) {
                                if (!isset($availableOptions[$option])) {
                                    $result->addError(new Error(
                                        Loc::getMessage('OPEN_SOURCE_ORDER_PROPERTY_ENUM_INVALID_OPTION', [
                                            '#PROPERTY_NAME#' => $prop->getName()
                                        ]),
                                        'property_' . $prop->getField('CODE'),
                                        [
                                            'id' => $prop->getId(),
                                            'code' => $prop->getField('CODE'),
                                            'type' => 'enum_invalid_option'
                                        ]
                                    ));
                                }
                            }
                        }
                        break;

                    case 'DATE':
                        $dateTime = \DateTime::createFromFormat(
                            \Bitrix\Main\Type\DateTime::getFormat(),
                            $prop->getValue()
                        );
                        if ($dateTime === false) {
                            $date = \DateTime::createFromFormat(
                                \Bitrix\Main\Type\Date::getFormat(),
                                $prop->getValue()
                            );
                            if ($date === false) {
                                $result->addError(new Error(
                                    Loc::getMessage('OPEN_SOURCE_ORDER_PROPERTY_DATE_WRONG_FORMAT', [
                                        '#PROPERTY_NAME#' => $prop->getName()
                                    ]),
                                    'property_' . $prop->getField('CODE'),
                                    [
                                        'id' => $prop->getId(),
                                        'code' => $prop->getField('CODE'),
                                        'type' => 'date_wrong_format'
                                    ]
                                ));
                            }
                        }
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * @return Result
     * @throws \Exception
     */
    public function validateDelivery(): Result
    {
        $result = new Result();

        /**
         * DELIVERY VALIDATION
         */
        $shipment = OrderHelper::getFirstNonSystemShipment($this->order);

        if ($shipment !== null) {
            if ($shipment->getDelivery() instanceof Delivery\Services\Base) {
                $obDelivery = $shipment->getDelivery();
                $availableDeliveries = Delivery\Services\Manager::getRestrictedObjectsList($shipment);
                if (!isset($availableDeliveries[$obDelivery->getId()])) {
                    $result->addError(new Error(
                        Loc::getMessage('OPEN_SOURCE_ORDER_DELIVERY_UNAVAILABLE'),
                        'delivery',
                        [
                            'type' => 'unavailable'
                        ]
                    ));
                }
            } else {
                $result->addError(new Error(
                    Loc::getMessage('OPEN_SOURCE_ORDER_NO_DELIVERY_SELECTED'),
                    'delivery',
                    [
                        'type' => 'undefined'
                    ]
                ));
            }
        } else {
            $result->addError(new Error(
                Loc::getMessage('OPEN_SOURCE_ORDER_SHIPMENT_NOT_FOUND'),
                'delivery',
                [
                    'type' => 'undefined'
                ]
            ));
        }

        return $result;
    }

    /**
     * @return Result
     * @throws \Exception
     */
    public function validatePayment(): Result
    {
        $result = new Result();

        /**
         * PAYMENT VALIDATION
         */
        if (count($this->order->getPaymentCollection()) < 1) {
            $result->addError(new Error(
                Loc::getMessage('OPEN_SOURCE_ORDER_NO_PAY_SYSTEM_SELECTED'),
                'payment',
                [
                    'type' => 'undefined'
                ]
            ));
        }

        return $result;
    }

    /**
     * @return Result
     * @throws \Exception
     */
    public function validateOrder(): Result
    {
        $result = new Result();

        $propValidationResult = $this->validateProperties();
        if (!$propValidationResult->isSuccess()) {
            $result->addErrors($propValidationResult->getErrors());
        }

        $deliveryValidationResult = $this->validateDelivery();
        if (!$deliveryValidationResult->isSuccess()) {
            $result->addErrors($deliveryValidationResult->getErrors());
        }

        $paymentValidationResult = $this->validatePayment();
        if (!$paymentValidationResult->isSuccess()) {
            $result->addErrors($paymentValidationResult->getErrors());
        }

        return $result;
    }

    public function executeComponent()
    {
        try {
            $this->createVirtualOrder($this->arParams['PERSON_TYPE_ID']);

            $propertiesList = $this->request['properties'] ?? $this->arParams['DEFAULT_PROPERTIES'] ?? [];
            $this->setOrderProperties($propertiesList);

            $deliveryId = $this->request['delivery_id'] ?? $this->arParams['DEFAULT_DELIVERY_ID'] ?? 0;
            $this->createOrderShipment($deliveryId);

            $paySystemId = $this->request['pay_system_id'] ?? $this->arParams['DEFAULT_PAY_SYSTEM_ID'] ?? 0;
            if ($paySystemId > 0) {
                $this->createOrderPayment($paySystemId);
            }

            if ($this->arParams['SAVE']) {
                $validationResult = $this->validateOrder();

                if ($validationResult->isSuccess()) {
                    $this->order->save();
                } else {
                    $this->errorCollection->add($validationResult->getErrors());
                }
            }
        } catch (\Exception $exception) {
            $this->errorCollection->setError(new Error($exception->getMessage()));
        }

        $this->includeComponentTemplate();
    }

}