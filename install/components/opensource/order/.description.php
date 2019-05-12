<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => Loc::getMessage('OPEN_SOURCE_ORDER_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('OPEN_SOURCE_ORDER_COMPONENT_DESCRIPTION'),
    'ICON' => '/images/news_detail.gif',
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'e-store',
    ],
];