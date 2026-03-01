<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\Config\Config;
use CekResi\Http\CurlClient;

abstract class AbstractCourier implements CourierInterface
{
    protected readonly CurlClient $http;

    public function __construct(
        protected readonly Config $config,
        ?CurlClient $http = null,
    ) {
        $this->http = $http ?? new CurlClient();
    }

    protected function formatDate(string|int|null $timestamp, string $format = 'd-m-Y H:i'): ?string
    {
        if ($timestamp === null || $timestamp === '' || $timestamp === 0) {
            return null;
        }

        if (is_int($timestamp)) {
            return date($format, $timestamp);
        }

        $time = strtotime($timestamp);
        return $time !== false ? date($format, $time) : null;
    }

    protected function env(string $key, ?string $default = null): ?string
    {
        return $this->config->get($key, $default);
    }

    protected function envRequired(string $key): string
    {
        return $this->config->getRequired($key);
    }
}
