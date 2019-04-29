<?php

namespace OpenSource\Order;

class LocationHelper
{
    /**
     * @param string $locationCode
     * @param array $excludeParts
     * @param string $order sort
     * @return array
     */
    public static function getDisplayByCode(string $locationCode, array $excludeParts = [], string $order = 'desc'): array
    {
        $result = [];

        if ($locationCode !== '') {
            $res = \Bitrix\Sale\Location\LocationTable::getList([
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