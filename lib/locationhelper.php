<?php

namespace OpenSource\Order;

use Bitrix\Sale\Location\LocationTable;

class LocationHelper
{
    /**
     * @param string $locationCode
     * @param array $excludeParts
     * @param string $order sort
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getDisplayByCode(string $locationCode, array $excludeParts = [], string $order = 'desc'): array
    {
        $result = [];

        if ($locationCode !== '') {
            $res = LocationTable::getList([
                'filter' => [
                    '=CODE' => $locationCode,
                    '!PARENTS.TYPE.CODE' => $excludeParts,
                    '=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '=PARENTS.TYPE.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                ],
                'select' => [
                    'PARENT_CODE' => 'PARENTS.CODE',
                    'NAME_LANG' => 'PARENTS.NAME.NAME',
                    'TYPE_CODE' => 'PARENTS.TYPE.CODE',
                    'TYPE_NAME_LANG' => 'PARENTS.TYPE.NAME.NAME'
                ],
                'order' => [
                    'PARENTS.DEPTH_LEVEL' => $order
                ]
            ]);

            $label = [];
            $partsList = [];
            while ($item = $res->fetch()) {
                $part = [
                    'name' => $item['NAME_LANG'],
                    'code' => $item['PARENT_CODE'],
                    'type' => $item['TYPE_CODE'],
                    'type_name' => $item['TYPE_NAME_LANG']
                ];

                $label[] = $part['name'];
                $partsList[$part['type']] = $part;

                if($part['code'] === $locationCode) {
                    $result = $part;
                }
            }

            $result['label'] = implode(', ', $label);
            $result['parts'] = $partsList;
        }

        return $result;
    }

}