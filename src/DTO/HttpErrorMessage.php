<?php

declare(strict_types=1);

namespace Apiera\WooPhpSdk\DTO;

final readonly class HttpErrorMessage
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private string $code,
        private string $message,
        private array $data,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
