<?php

declare(strict_types=1);

namespace CekResi\Http;

final class CurlClient
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_MAX_REDIRECTS = 10;

    public function request(
        string $url,
        HttpMethod $method = HttpMethod::GET,
        ?string $body = null,
        array $headers = [],
        int $timeout = self::DEFAULT_TIMEOUT,
    ): CurlResponse {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => self::DEFAULT_MAX_REDIRECTS,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method->value,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            return new CurlResponse(
                httpCode: 0,
                body: null,
                error: $error ?: 'cURL request failed',
            );
        }

        return new CurlResponse(
            httpCode: $httpCode,
            body: $response,
            error: null,
        );
    }

    public function get(string $url, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): CurlResponse
    {
        return $this->request($url, HttpMethod::GET, null, $headers, $timeout);
    }

    public function post(string $url, string $body, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): CurlResponse
    {
        return $this->request($url, HttpMethod::POST, $body, $headers, $timeout);
    }

    public function postForm(string $url, array $data, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): CurlResponse
    {
        return $this->request($url, HttpMethod::POST, http_build_query($data), $headers, $timeout);
    }
}
