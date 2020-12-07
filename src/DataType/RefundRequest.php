<?php

declare(strict_types = 1);

namespace Cheppers\OtpspClient\DataType;

class RefundRequest extends RequestBase
{

    /**
     * @var string
     */
    public $transactionId = '';

    /**
     * @var string
     */
    public $orderRef = '';

    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var float
     */
    public $refundTotal = 0.0;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [];

        foreach (array_keys(get_object_vars($this)) as $key) {
            switch ($key) {
                case 'salt':
                    $data['salt'] = $this->salt;
                    break;
                case 'orderRef':
                    $data['orderRef'] = $this->orderRef;
                    break;
                case 'transactionId':
                    $data['transactionId'] = $this->transactionId;
                    break;
                case 'merchant':
                    $data['merchant'] = $this->merchant;
                    break;
                case 'currency':
                    $data['currency'] = $this->currency;
                    break;
                case 'refundTotal':
                    $data['refundTotal'] = $this->refundTotal;
                    break;
                case 'sdkVersion':
                    $data['sdkVersion'] = $this->sdkVersion;
                    break;
            }
        }

        return $data;
    }
}
