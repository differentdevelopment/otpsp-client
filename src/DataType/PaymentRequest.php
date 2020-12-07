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
class PaymentRequest extends RequestBase
{

    /**
     * {@inheritdoc}
     */
    public static function __set_state($values)
    {
        $instance = new static();

        foreach (array_keys(get_object_vars($instance)) as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            switch ($key) {
                case 'delivery':
                    $instance->delivery = Address::__set_state($values['delivery']);
                    break;
                case 'invoice':
                    $instance->invoice = Address::__set_state($values['invoice']);
                    break;
                case 'urls':
                    $instance->urls = Urls::__set_state($values['urls']);
                    break;
                case 'items':
                    foreach ($values['items'] as $item) {
                        $instance->items[] = Item::__set_state($item);
                    }
                    break;
                default:
                    $instance->{$key} = $values[$key];
            }
        }

        return $instance;
    }

    /**
     * @var string
     */
    public $orderRef = '';

    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var string
     */
    public $customer = '';

    /**
     * @var string
     */
    public $customerEmail = '';

    /**
     * @var string
     */
    public $language = '';

    /**
     * @var string
     */
    public $total = '';

    /**
     * @var string[]
     */
    public $methods = ['CARD'];

    /**
     * @var Address
     */
    public $invoice;

    /**
     * @var Address
     */
    public $delivery;

    /**
     * @var Item[]
     */
    public $items = [];

    /**
     * @var int
     */
    public $shippingCost = 0;

    /**
     * @var int
     */
    public $discount = 0;

    /**
     * Date and time in the future.
     *
     * Format 'Y-m-d\TH:i:sP'.
     *
     * @var string
     */
    public $timeout = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var Urls
     */
    public $urls;

    public function __construct()
    {
        $this->invoice = new Address();
        $this->delivery = new Address();
        $this->urls = new Urls();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            switch ($key) {
                case 'merchant':
                case 'orderRef':
                case 'customer':
                case 'customerEmail':
                case 'language':
                case 'currency':
                case 'total':
                case 'salt':
                case 'methods':
                case 'shippingCost':
                case 'discount':
                case 'timeout':
                case 'url':
                case 'sdkVersion':
                    $data[$key] = $value;
                    break;
                case 'invoice':
                    $data[$key] = $this->invoice->exportData();
                    break;
                case 'delivery':
                    if ($this->delivery->zip === ''
                        || $this->delivery->address === ''
                        || $this->delivery->city === ''
                    ) {
                        break;
                    }
                    $data[$key] = $this->delivery->exportData();
                    break;
                case 'items':
                    foreach ($this->items as $item) {
                        $data[$key][] = $item->exportData();
                    }
                    break;
                case 'urls':
                    if ($this->urls->success === ''
                        && $this->urls->cancel === ''
                        && $this->urls->fail === ''
                        && $this->urls->timeout === ''
                    ) {
                        break;
                    }
                    $data[$key] = $this->urls->exportData();
                    break;
            }
        }

        return $data;
    }
}
