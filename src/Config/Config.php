<?php

declare(strict_types=1);

namespace CekResi\Config;

use Dotenv\Dotenv;

final class Config
{
    private static ?self $instance = null;

    private function __construct(
        private readonly array $values,
    ) {}

    public static function load(string $basePath): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->load();

        self::$instance = new self($_ENV);

        return self::$instance;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->values[$key] ?? $default;
    }

    public function getRequired(string $key): string
    {
        return $this->values[$key]
            ?? throw new \RuntimeException("Missing required config key: {$key}");
    }

    public function getTimezone(): string
    {
        return $this->get('APP_TIMEZONE', 'Asia/Jakarta');
    }

    public function isDebug(): bool
    {
        return filter_var($this->get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Reset singleton (useful for testing) */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
