<?php

declare(strict_types = 1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class App
{

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    public function getMerchantId(): string
    {
        return getenv('OTPSP_MERCHANT_ID') ?: 'PUBLICTESTHUF';
    }

    public function getSecretKey(): string
    {
        return getenv('OTPSP_SECRET_KEY') ?: 'FxDa5w314kLlNseq2sKuVwaqZshZT5d6';
    }

    public function getBaseUrl(): string
    {
        return getenv('OTPSP_BASE_URL') ?: 'http://otpsp-client.test';
    }

    public function twig()
    {
        if (!$this->twig) {
            $loader = new FilesystemLoader(__DIR__ . '/templates');
            $this->twig = new Environment($loader);
        }

        return $this->twig;
    }

    public function requestUri(): string
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    public function jsonEncode($data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function log(string $channel, string $message)
    {
        $logDir = __DIR__ . '/log';

        if (!file_exists($logDir)) {
            mkdir($logDir, 0777 - umask(), true);
        }

        $level = 'DEBUG';
        $date = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        file_put_contents(
            "$logDir/app.log",
            "[$date] [$level] $channel $message\n",
            FILE_APPEND
        );

        return $this;
    }
}
