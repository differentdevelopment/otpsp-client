<?php

declare(strict_types = 1);

namespace Cheppers\OtpspClient\DataType;

use JsonSerializable;

class InstantPaymentNotification extends ResponseBase implements JsonSerializable
{

    /**
     * @var string
     */
    public $method = 'CARD';

    /**
     * @var string
     */
    public $finishDate = '';

    /**
     * @var string
     */
    public $paymentDate = '';

    /**
     * @var string
     */
    public $status = '';

    /**
     * @var string
     */
    public $receiveDate = '';

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            switch ($key) {
                case 'salt':
                case 'merchant':
                case 'orderRef':
                case 'transactionId':
                case 'method':
                case 'finishDate':
                case 'paymentDate':
                case 'status':
                case 'receiveDate':
                    $data[$key] = $value;
                    break;
            }
        }

        return $data;
    }
}
