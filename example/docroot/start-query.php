<?php

declare(strict_types = 1);

use Cheppers\OtpspClient\Checksum;
use Cheppers\OtpspClient\DataType\QueryRequest;
use Cheppers\OtpspClient\OtpSimplePayClient;
use GuzzleHttp\Client;
use Psr\Log\Test\TestLogger;

require_once '../../vendor/autoload.php';
require_once '../App.php';

$app = new App();

$now = new DateTime('now');
$guzzle = new Client();
$serializer = new Checksum();
$logger = new TestLogger();
$otpSimple = new OtpSimplePayClient($guzzle, $serializer, $logger);
$otpSimple->setSecretKey($app->getSecretKey());
$timeout = new DateInterval('PT5M');

$queryRequest = new QueryRequest();
$queryRequest->merchant = $app->getMerchantId();
//$queryRequest->orderRefs[] = 'my-order-id-2020-12-07-09-35-51';
$queryRequest->salt = 'd471d2fb24c5a395563ff60f8ba769d1';
$queryRequest->transactionIds[] = '500748082';
//$queryRequest->salt = 'TV0ywJZVdf62p5nAJkldHWDzr2dLJRPe';

$startQueryResponse = $otpSimple->startQuery($queryRequest);

// In a real application do not print anything,
// just redirect the client to $startPaymentResponse->paymentURL.
echo $app
    ->twig()
    ->render(
        'start-query.html.twig',
        [
            'queryRequest' => $queryRequest,
            'queryRequestJson' => $app->jsonEncode($queryRequest),
            'queryPaymentResponse' => $startQueryResponse,
            'startQueryResponseJson' => $app->jsonEncode($startQueryResponse),
            'logEntriesJson' => $app->jsonEncode($logger->records),
        ]
    );
