<?php
declare(strict_types = 1);
namespace Cheppers\OtpspClient\DataType;

class QueryResponse extends ResponseBase
{
    /**
     * @var double
     */
    public $totalCount = 0;
    /**
     * @var array
     */
    public $transactions = [];
}
