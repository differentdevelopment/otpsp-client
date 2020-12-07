<?php

declare(strict_types = 1);

namespace Cheppers\OtpspClient\DataType;

use JsonSerializable;

abstract class RequestBase implements JsonSerializable
{

    public static function __set_state($values)
    {
        $instance = new static();
        foreach (array_keys(get_object_vars($instance)) as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $instance->{$key} = $values[$key];
        }

        return $instance;
    }

    /**
     * @var string
     */
    public $merchant = '';

    /**
     * @var string
     */
    public $salt = '';

    /**
     * @var string
     */
    public $sdkVersion = 'SimplePayV2.1_Payment_PHP_SDK_2.0.7_190701:dd236896400d7463677a82a47f53e36e';
//    public $sdkVersion = 'SimplePay_PHP_SDK_2.0_180930:33ccd5ed8e8a965d18abfae333404184';
}
