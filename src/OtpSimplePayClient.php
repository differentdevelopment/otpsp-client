<?php

declare(strict_types = 1);

namespace Cheppers\OtpspClient;

use Cheppers\OtpspClient\DataType\BackResponse;
use Cheppers\OtpspClient\DataType\InstantPaymentNotification;
use Cheppers\OtpspClient\DataType\PaymentRequest;
use Cheppers\OtpspClient\DataType\QueryRequest;
use Cheppers\OtpspClient\DataType\QueryResponse;
use Cheppers\OtpspClient\DataType\RefundRequest;
use Cheppers\OtpspClient\DataType\RefundResponse;
use Cheppers\OtpspClient\DataType\RequestBase;
use Cheppers\OtpspClient\DataType\PaymentResponse;
use DateTime;
use DateTimeInterface;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class OtpSimplePayClient implements OtpSimplePayClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    protected $baseUri = 'https://sandbox.simplepay.hu/payment/v2';

    /**
     * {@inheritdoc}
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseUri(string $value)
    {
        $this->baseUri = $value;

        return $this;
    }

    public function setBaseUriByMode(string $mode)
    {
        $mode = in_array($mode, ['secure', 'live', 'prod', 'production']) ?
            'secure'
            : 'sandbox';

        $uri = "https://$mode.simplepay.hu/payment/v2";

        return $this->setBaseUri($uri);
    }

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @var \Cheppers\OtpspClient\ChecksumInterface
     */
    protected $checksum;

    public function getChecksum(): ChecksumInterface
    {
        return $this->checksum;
    }

    /**
     * @return $this
     */
    public function setChecksum(ChecksumInterface $checksum)
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * @var \DateTimeInterface
     */
    protected $now;

    public function getNow(): DateTimeInterface
    {
        return $this->now;
    }

    /**
     * @return $this
     */
    public function setNow(DateTimeInterface $now)
    {
        $this->now = $now;

        return $this;
    }

    /**
     * @var string
     */
    protected $secretKey = '';

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @return $this
     */
    public function setSecretKey(string $secretKey)
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * @var string[]
     */
    protected $supportedLanguages = [
        'cz',
        'de',
        'en',
        'es',
        'it',
        'hr',
        'hu',
        'pl',
        'ro',
        'sk',
    ];

    /**
     * @return string[]
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    public function __construct(
        ClientInterface $client,
        ChecksumInterface $checksumCalculator,
        LoggerInterface $logger,
        ?DateTimeInterface $now = null
    ) {
        $this
            ->setClient($client)
            ->setChecksum($checksumCalculator)
            ->setNow($now ?: new DateTime())
            ->setLogger($logger);
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function startPayment(PaymentRequest $paymentRequest): PaymentResponse
    {
        $response = $this->sendRequest($paymentRequest, 'start');
        $body = $this->getMessageBody($response);

        return PaymentResponse::__set_state($body);
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function startRefund(RefundRequest $refundRequest): RefundResponse
    {
        $response = $this->sendRequest($refundRequest, 'refund');
        $body = $this->getMessageBody($response);

        return RefundResponse::__set_state($body);
    }

    /**
     * @param  QueryRequest  $queryRequest
     * @return QueryResponse
     * @throws GuzzleException
     */
    public function startQuery(QueryRequest $queryRequest): QueryResponse
    {
        $response = $this->sendRequest($queryRequest, 'query');
        $body = $this->getMessageBody($response);

        return QueryResponse::__set_state($body);
    }

    /**
     * @throws Exception
     */
    public function parseBackResponse(string $url): BackResponse
    {
        $values = Utils::getQueryFromUrl($url);

        if (!array_key_exists('r', $values) || !array_key_exists('s', $values)) {
            throw new Exception('Invalid response');
        }

        $responseMessage = base64_decode($values['r']);

        if (!$this->getChecksum()->verify($this->getSecretKey(), $responseMessage, $values['s'])) {
            throw new Exception('Invalid response');
        }

        $body = json_decode($responseMessage, true);
        if (!is_array($body)) {
            throw new Exception('Response message is not a valid JSON', 4);
        }

        return BackResponse::__set_state($body);
    }

    /**
     * @throws Exception
     */
    public function parseInstantPaymentNotificationRequest(RequestInterface $request): ?InstantPaymentNotification
    {
        $body = $this->getMessageBody($request);

        return InstantPaymentNotification::__set_state($body);
    }

    /**
     * @throws Exception
     */
    public function parseInstantPaymentNotificationMessage(
        string $signature,
        string $bodyContent
    ): ?InstantPaymentNotification {
        if (!$this->getChecksum()->verify($this->secretKey, $bodyContent, $signature)) {
            throw new Exception('Response checksum mismatch');
        }

        $message = json_decode($bodyContent, true);

        if (!is_array($message)) {
            throw new Exception('Response body is not a valid JSON', 5);
        }

        // @todo Schema validation.
        return InstantPaymentNotification::__set_state($message);
    }

    public function getInstantPaymentNotificationSuccessResponse(
        InstantPaymentNotification $ipn
    ): ResponseInterface {
        $parts = $this->getInstantPaymentNotificationSuccessParts($ipn);

        return new Response($parts['statusCode'], $parts['headers'], $parts['body']);
    }

    public function getInstantPaymentNotificationSuccessParts(InstantPaymentNotification $ipn): array
    {
        if (empty($ipn->receiveDate)) {
            $ipn->receiveDate = $this->getNow()->format(static::DATETIME_FORMAT);
        }

        $message = json_encode($ipn);

        return [
            'statusCode' => 200,
            'body' => $message,
            'headers' => [
                'Content-Type' => 'application/json',
                'Signature' => $this->getChecksum()->calculate($this->secretKey, $message),
            ],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function sendRequest(RequestBase $requestType, string $path): ResponseInterface
    {
        $requestMessage = json_encode($requestType->jsonSerialize());

        $header = [
            'Content-type' => 'application/json',
            'Signature' => $this->getChecksum()->calculate($this->secretKey, $requestMessage),
        ];

        return $this->client->send(new Request('POST', $this->getUri($path), $header, $requestMessage));
    }

    protected function getUri(string $path): string
    {
        return $this->getBaseUri() . "/$path";
    }

    /**
     * @throws Exception
     */
    protected function getMessageBody(MessageInterface $message): array
    {
        if (!$message->hasHeader('Content-Type')) {
            throw new Exception('Missing header Content-Type', 1);
        }

        $allowedContentTypes = [
            'application/json;charset=UTF-8',
            'application/json',
        ];
        if (!array_intersect($allowedContentTypes, $message->getHeader('Content-Type'))) {
            throw new Exception('Not allowed Content-Type', 2);
        }

        if (!$message->hasHeader('signature')) {
            throw new Exception('Response has no signature', 3);
        }

        $signature = $message->getHeader('signature')[0];
        $bodyContent = $message->getBody()->getContents();
        if (!$this->getChecksum()->verify($this->getSecretKey(), $bodyContent, $signature)) {
            throw new Exception('Response checksum mismatch', 4);
        }

        $body = json_decode($bodyContent, true);
        if (!is_array($body)) {
            throw new Exception('Response body is not a valid JSON', 5);
        }

        return $body;
    }
}
