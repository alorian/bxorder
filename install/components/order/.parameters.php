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

$arDeliveries = [
    0 => Loc::getMessage('OPEN_SOURCE_DEFAULT_VALUE_EMPTY')
];

$arPaySystems = [
    0 => Loc::getMessage('OPEN_SOURCE_DEFAULT_VALUE_EMPTY')
];


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
            'VALUES' => $arDeliveries
        ],
        'DEFAULT_PAY_SYSTEM_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_DEFAULT_PAY_SYSTEM_ID'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'N',
            'DEFAULT' => '/personal/cart/',
            'PARENT' => 'BASE',
            'VALUES' => $arPaySystems
        ],
        'USE_DEFAULT_USER_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_USE_DEFAULT_USER_ID'),
            'TYPE' => 'CHECKBOX',
            'MULTIPLE' => 'N',
            'DEFAULT' => 'N',
            'PARENT' => 'ADDITIONAL_SETTINGS',
        ],
        'DEFAULT_USER_ID' => [
            'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_DEFAULT_USER_ID'),
            'TYPE' => 'STRING',
            'MULTIPLE' => 'N',
            'DEFAULT' => '',
            'PARENT' => 'ADDITIONAL_SETTINGS',
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