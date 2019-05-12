<?php

use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var OpenSourceOrderComponent $component */
?>
<form action="" method="post" name="os-order-form" id="os-order-form">

    <h2>Тип плательщика</h2>
    <div class="person-type-selector">
        <? foreach ($arResult['PERSON_TYPES'] as $arPersonType): ?>
            <label>
                <input type="radio" name="person_type_id"
                       value="<?= $arPersonType['ID'] ?>" <?= $arPersonType['CHECKED'] ? 'checked' : '' ?>>
                <?= $arPersonType['NAME'] ?>
            </label>
            <br>
        <? endforeach; ?>
    </div>

    <h2>Свойства заказа:</h2>

    <?php foreach ($arResult['PROPERTIES'] as $personTypeId => $propertiesList): ?>
        <table class="properties-table properties-<?= $personTypeId ?> <?= $arParams['PERSON_TYPE_ID'] === $personTypeId ? 'active' : '' ?>">
            <? foreach ($propertiesList as $arProp): ?>
                <tr>
                    <td>
                        <label for="<?= $arProp['FORM_LABEL'] ?>"><?= $arProp['NAME'] ?></label>
                        <? foreach ($arProp['ERRORS'] as $error):
                            /** @var Error $error */
                            ?>
                            <div class="error"><?= $error->getMessage() ?></div>
                        <? endforeach; ?>
                    </td>
                    <td>
                        <?php
                        switch ($arProp['TYPE']):
                            case 'LOCATION':
                                ?>
                                <div class="location">
                                    <select class="location-search" name="<?= $arProp['FORM_NAME'] ?>"
                                            id="<?= $arProp['FORM_LABEL'] ?>">
                                        <option
                                                data-data='<?
                                                echo Json::encode($arProp['LOCATION_DATA']) ?>'
                                                value="<?= $arProp['VALUE'] ?>"><?= $arProp['LOCATION_DATA']['label'] ?></option>
                                    </select>
                                </div>
                                <?
                                break;

                            case 'ENUM':
                                foreach ($arProp['OPTIONS'] as $code => $name):?>
                                    <label class="enum-option">
                                        <input type="radio" name="<?= $arProp['FORM_NAME'] ?>" value="<?= $code ?>">
                                        <?= $name ?>
                                    </label>
                                <?endforeach;
                                break;

                            case 'DATE':
                                $APPLICATION->IncludeComponent(
                                    'bitrix:main.calendar',
                                    '',
                                    [
                                        'SHOW_INPUT' => 'Y',
                                        'FORM_NAME' => 'os-order-form',
                                        'INPUT_NAME' => $arProp['FORM_NAME'],
                                        'INPUT_VALUE' => $arProp['VALUE'],
                                        'SHOW_TIME' => 'Y',
                                        //'HIDE_TIMEBAR' => 'Y',
                                        'INPUT_ADDITIONAL_ATTR' => 'placeholder="выберите дату"'
                                    ]
                                );
                                break;

                            case 'Y/N':
                                ?>
                                <input id="<?= $arProp['FORM_LABEL'] ?>" type="checkbox"
                                       name="<?= $arProp['FORM_NAME'] ?>"
                                       value="Y">
                                <?
                                break;

                            default:
                                ?>
                                <input id="<?= $arProp['FORM_LABEL'] ?>" type="text"
                                       name="<?= $arProp['FORM_NAME'] ?>"
                                       value="<?= $arProp['VALUE'] ?>">
                            <? endswitch; ?>
                    </td>
                </tr>
            <? endforeach; ?>
        </table>
    <? endforeach; ?>


    <h2>Службы доставки:</h2>
    <? foreach ($arResult['DELIVERY_ERRORS'] as $error):
        /** @var Error $error */
        ?>
        <div class="error"><?= $error->getMessage() ?></div>
    <? endforeach;
    foreach ($arResult['DELIVERY_LIST'] as $arDelivery):?>
        <label>
            <input type="radio" name="delivery_id"
                   value="<?= $arDelivery['ID'] ?>"
                <?= $arDelivery['CHECKED'] ? 'checked' : '' ?>
            >
            <?= $arDelivery['NAME'] ?>,
            <?= $arDelivery['PRICE_DISPLAY'] ?>
        </label>
        <br>
    <? endforeach; ?>

    <h2>Платежные системы:</h2>
    <? foreach ($arResult['PAY_SYSTEM_ERRORS'] as $error):
        /** @var Error $error */
        ?>
        <div class="error"><?= $error->getMessage() ?></div>
    <? endforeach;
    foreach ($arResult['PAY_SYSTEM_LIST'] as $arPaySystem): ?>
        <label>
            <input type="radio" name="pay_system_id"
                   value="<?= $arPaySystem['ID'] ?>"
                <?= $arPaySystem['CHECKED'] ? 'checked' : '' ?>
            >
            <?= $arPaySystem['NAME'] ?>
        </label>
        <br>
    <? endforeach; ?>

    <h2>Состав заказа</h2>
    <table>
        <tr>
            <th>Название</th>
            <th>Количество</th>
            <th>Цена за штуку</th>
            <th>Цена со скидкой</th>
            <th>Итого</th>
        </tr>
        <? foreach ($arResult['BASKET'] as $arBasketItem): ?>
            <tr>
                <td>
                    <?= $arBasketItem['NAME'] ?>
                    <? if (!empty($arBasketItem['PROPERTIES'])): ?>
                        <div class="basket-properties">
                            <? foreach ($arBasketItem['PROPERTIES'] as $arProp): ?>
                                <?= $arProp['NAME'] ?>
                                <?= $arProp['VALUE'] ?>
                                <br>
                            <? endforeach; ?>
                        </div>
                    <? endif; ?>
                </td>
                <td><?= $arBasketItem['QUANTITY_DISPLAY'] ?></td>
                <td><?= $arBasketItem['BASE_PRICE_DISPLAY'] ?></td>
                <td><?= $arBasketItem['PRICE_DISPLAY'] ?></td>
                <td><?= $arBasketItem['SUM_DISPLAY'] ?></td>
            </tr>
        <? endforeach; ?>
    </table>

    <h2>Итоговые цифры</h2>
    <h3>Цены товаров:</h3>
    <table>
        <tr>
            <td>Стоимость товаров без скидок</td>
            <td><?= $arResult['PRODUCTS_BASE_PRICE_DISPLAY'] ?></td>
        </tr>
        <tr>
            <td>Стоимость товаров со скидками</td>
            <td><?= $arResult['PRODUCTS_PRICE_DISPLAY'] ?></td>
        </tr>
        <tr>
            <td>Скидка на товары</td>
            <td><?= $arResult['PRODUCTS_DISCOUNT_DISPLAY'] ?></td>
        </tr>
    </table>

    <h3>Стоимость доставки:</h3>
    <table>
        <tr>
            <td>Стоимость доставки без учета скидок</td>
            <td><?= $arResult['DELIVERY_BASE_PRICE_DISPLAY'] ?></td>
        </tr>
        <tr>
            <td>Стоимость доставки со скидками</td>
            <td><?= $arResult['DELIVERY_PRICE_DISPLAY'] ?></td>
        </tr>
        <tr>
            <td>Скидка на доставку</td>
            <td><?= $arResult['DELIVERY_DISCOUNT_DISPLAY'] ?></td>
        </tr>
    </table>

    <h3>Заказ целиком:</h3>
    <table>
        <tr>
            <td>Общая цена без скидок</td>
            <td><?= $arResult['SUM_BASE_DISPLAY'] ?></td>
        </tr>
        <tr>
            <td>Общая скидка</td>
            <td><?= $arResult['DISCOUNT_VALUE_DISPLAY'] ?></td>
        </tr>
        <tr>
            <td>К оплате</td>
            <td><?= $arResult['SUM_DISPLAY'] ?></td>
        </tr>
    </table>

    <input type="hidden" name="save" value="y">
    <br>
    <button type="submit">Оформить заказ</button>
    <br>
    <br>

</form>
