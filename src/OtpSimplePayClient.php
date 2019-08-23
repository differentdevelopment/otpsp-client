<?php

declare(strict_types = 1);

namespace Cheppers\OtpspClient;

use Cheppers\OtpspClient\DataType\BackResponse;
use Cheppers\OtpspClient\DataType\InstantPaymentNotification;
use Cheppers\OtpspClient\DataType\PaymentRequest;
use Cheppers\OtpspClient\DataType\RequestBase;
use Cheppers\OtpspClient\DataType\StartResponse;
use DateTimeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class OtpSimplePayClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \Cheppers\OtpspClient\Checksum
     */
    protected $checksum;

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

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client = null;

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
     * @var \DateTimeInterface
     */
    protected $dateTime;

    public function getDateTime(): DateTimeInterface
    {
        return $this->dateTime;
    }

    /**
     * @return $this
     */
    public function setDateTime(DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime;

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
        Checksum $serializer,
        LoggerInterface $logger,
        DateTimeInterface $dateTime
    ) {
        $this->client = $client;
        $this->checksum = $serializer;
        $this->setLogger($logger);
        $this->dateTime = $dateTime;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function startPayment(PaymentRequest $paymentRequest): ?StartResponse
    {
        $response = $this->createRequest($paymentRequest, 'start');

        $signature = $response->getHeader('signature')[0];
        $message = $response->getBody()->getContents();

        if ($signature === []
            || $message === ''
            || $response->getHeader('Content-Type')[0] !== 'application/json;charset=UTF-8'
        ) {
            throw new \Exception('Starting payment failed', 1);
        }

        if (!$this->isValidChecksum($signature, $message)) {
            throw new \Exception('Invalid response', 1);
        }

        $data = json_decode($message, true);

        if ($data === false) {
            throw new \Exception('Invalid json response', 1);
        }

        return StartResponse::__set_state($data);
    }

    /**
     * @throws \Exception
     */
    public function parseBackResponse(string $url): ?BackResponse
    {
        $values = Utils::getQueryFromUrl($url);

        if (!$values['r'] || !$values['s']) {
            throw new \Exception('Invalid response');
        }

        $responseMessage = base64_decode($values['r']);

        if (!$this->isValidChecksum($values['s'], $responseMessage)) {
            throw new \Exception('Invalid response');
        }

        return BackResponse::__set_state(json_decode($responseMessage, true));
    }

    /**
     * @throws \Exception
     */
    public function parseInstantPaymentNotificationRequest(Request $request): ?InstantPaymentNotification
    {
        $signature = $request->getHeader('Signature')[0];
        $message = $request->getBody()->getContents();

        if (!$signature || !$message || !$this->isValidChecksum($signature, $message)) {
            throw new \Exception('Invalid response', 1);
        }

        $data = json_decode($message, true);

        if ($data === false || $data === null) {
            throw new \Exception('Invalid json string', 1);
        }

        return InstantPaymentNotification::__set_state($data);
    }

    public function getInstantPaymentNotificationSuccessResponse(
        InstantPaymentNotification $instantPaymentNotification
    ): ResponseInterface
    {
        $instantPaymentNotification->receiveDate = (new \DateTime('now'))->format('Y-m-d\TH:i:sP');

        $message = json_encode($instantPaymentNotification);
        print_r($message);
        return new Response(
            200,
            [
                'Content-Type' => 'application/json',
                'Signature' => $this->checksum->calculate($message, $this->secretKey),
            ],
            $message
        );
    }

    protected function getUri(string $path): string
    {
        return $this->getBaseUri() . "/$path";
    }

    /**
     * {@inheritdoc}
     */
    public function isValidChecksum(string $expectedHash, string $values): bool
    {
        $actualHash = $this
            ->checksum
            ->calculate($values, $this->getSecretKey());

        return $expectedHash === $actualHash;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createRequest(RequestBase $requestType, string $path): ResponseInterface
    {
        $requestMessage = json_encode($requestType->jsonSerialize());

        $header = [
            'Content-type' => 'application/json',
            'Signature' => $this->checksum->calculate($requestMessage, $this->secretKey),
        ];

        return $this->client->send(new Request('POST', $this->getUri($path), $header, $requestMessage));
    }
}
