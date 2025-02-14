<?php

declare(strict_types=1);

namespace Apiera\WooPhpSdk\Exception\Http;

use Apiera\WooPhpSdk\DTO\HttpErrorMessage;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Base exception for all HTTP/API related errors
 *
 * @author Fredrik Tveraaen <fredrik.tveraaen@apiera.io>
 * @since 0.1.0
 */
abstract class HttpException extends Exception
{
    /** @var array<string, mixed>|null */
    private ?array $responseData = null;

    /** @var array<string, mixed>|null */
    private ?array $requestData = null;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ?ResponseInterface $response = null,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getRequestMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getRequestUri(): string
    {
        return (string) $this->request->getUri();
    }

    /**
     * @return array<string, string[]>
     */
    public function getRequestHeaders(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestData(): ?array
    {
        if ($this->requestData !== null) {
            return $this->requestData;
        }

        $body = (string) $this->request->getBody();

        if (strlen($body) === 0) {
            return null;
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $this->requestData = $data;

        return $data;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function getResponseStatusCode(): ?int
    {
        return $this->response?->getStatusCode();
    }

    /**
     * @return array<string, string[]>
     */
    public function getResponseHeaders(): array
    {
        return $this->response?->getHeaders() ?? [];
    }

    public function getErrorMessage(): HttpErrorMessage
    {
        if ($this->response === null) {
            return new HttpErrorMessage(
                'unknown_error',
                $this->getMessage() !== '' ? $this->getMessage() : 'An unknown error occurred',
                ['status' => 0]
            );
        }

        $data = $this->getResponseData();

        if ($data === null || !isset($data['code'], $data['message'])) {
            return new HttpErrorMessage(
                'invalid_response',
                'Invalid response received from the API',
                ['status' => $this->response->getStatusCode()]
            );
        }

        return new HttpErrorMessage(
            $data['code'],
            $data['message'],
            $data['data'] ?? ['status' => $this->response->getStatusCode()]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getResponseData(): ?array
    {
        if ($this->responseData !== null) {
            return $this->responseData;
        }

        if ($this->response === null) {
            return null;
        }

        $body = (string) $this->response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $this->responseData = $data;

        return $data;
    }
}
