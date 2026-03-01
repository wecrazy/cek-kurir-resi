<?php

declare(strict_types=1);

namespace CekResi\Http;

final readonly class CurlResponse
{
    public function __construct(
        public int $httpCode,
        public ?string $body,
        public ?string $error,
    ) {}

    public function isSuccess(): bool
    {
        return $this->httpCode >= 200 && $this->httpCode < 300 && $this->error === null;
    }

    public function json(): ?array
    {
        if ($this->body === null) {
            return null;
        }

        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function failed(): bool
    {
        return !$this->isSuccess();
    }
}
