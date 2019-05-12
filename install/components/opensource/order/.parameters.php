<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::includeModule('sale');

$arPersonTypesList = [];
$rsPersonTypes = \CSalePersonType::GetList(['SORT' => 'ASC']);
while ($arPersonType = $rsPersonTypes->Fetch()) {
    $arPersonTypesList[$arPersonType['ID']] = '[' . $arPersonType['ID'] . '] ' . $arPersonType['NAME'];
}


$arDeliveriesList = [
    0 => Loc::getMessage('OPEN_SOURCE_DEFAULT_VALUE_EMPTY')
];
$arActiveDeliveries = Bitrix\Sale\Delivery\Services\Manager::getActiveList();
foreach ($arActiveDeliveries as $arDelivery) {
    $arDeliveriesList[$arDelivery['ID']] = '[' . $arDelivery['ID'] . '] ' . $arDelivery['NAME'];
}


$arPaySystemsList = [
    0 => Loc::getMessage('OPEN_SOURCE_DEFAULT_VALUE_EMPTY')
];
$rsPaySystems = Bitrix\Sale\PaySystem\Manager::getList();
while ($arPaySystem = $rsPaySystems->fetch()) {
    $arPaySystemsList[$arPaySystem['ID']] = '[' . $arPaySystem['ID'] . '] ' . $arPaySystem['NAME'];
}

$arComponentParameters = [
    'GROUPS' => [
    ],
    'PARAMETERS' => [
        'DEFAULT_PERSON_TYPE_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_DEFAULT_PERSON_TYPE_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arPersonTypesList
        ],
        'DEFAULT_DELIVERY_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_DEFAULT_DELIVERY_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arDeliveriesList
        ],
        'DEFAULT_PAY_SYSTEM_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_DEFAULT_PAY_SYSTEM_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arPaySystemsList
        ],
        'PATH_TO_BASKET' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_PATH_TO_BASKET'),
            'TYPE' => 'STRING',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ]
    ]
];