<?php

declare(strict_types = 1);

namespace Cheppers\OtpspClient\DataType;

/**
 * Represent a data structure to create a transaction.
 *
 * Endpoint https://sandbox.simplepay.hu/payment/v2/start
 *
 * @see http://simplepartner.hu/download.php?target=v21docen Chapter 3.3
 */
class QueryRequest extends RequestBase
{

    /**
     * @var string
     */
    public $merchant = '';

    /**
     * @var array
     */
    public $transactionIds = [];

//    /**
//     * @var array
//     */
//    public $orderRefs = [];

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            $data[$key] = $value;

        }
            return $data;
    }
}
