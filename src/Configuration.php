<?php

declare(strict_types=1);

namespace Apiera\WooPhpSdk;

use Apiera\WooPhpSdk\Enum\ApiVersion;

/**
 * @author Fredrik Tveraaen <fredrik.tveraaen@apiera.io>
 * @since 0.1.0
 */
final readonly class Configuration
{
    public function __construct(
        private string $baseUrl,
        private string $consumerKey,
        private string $consumerSecret,
        private ApiVersion $apiVersion,
        private string $userAgent,
        private int $timeout,
    ) {
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    public function getConsumerSecret(): string
    {
        return $this->consumerSecret;
    }

    public function getApiVersion(): ApiVersion
    {
        return $this->apiVersion;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
