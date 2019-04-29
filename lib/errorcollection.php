<?php

namespace OpenSource\Order;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection as BitrixErrorCollection;

class ErrorCollection extends BitrixErrorCollection
{

    /**
     * Returns array of errors with the necessary code.
     * @param string $code Code of error.
     * @return Error[]
     */
    public function getAllErrorsByCode($code): array
    {
        $errorsList = [];

        foreach ($this->values as $error) {
            /** @var Error $error */
            if ($error->getCode() === $code) {
                $errorsList[] = $error;
            }
        }

        return $errorsList;
    }

}