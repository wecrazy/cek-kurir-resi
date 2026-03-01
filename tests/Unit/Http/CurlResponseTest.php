<?php

declare(strict_types=1);

namespace CekResi\Tests\Unit\Http;

use CekResi\Http\CurlResponse;
use PHPUnit\Framework\TestCase;

final class CurlResponseTest extends TestCase
{
    public function testSuccessfulResponse(): void
    {
        $response = new CurlResponse(httpCode: 200, body: '{"ok":true}', error: null);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->failed());
        $this->assertSame(['ok' => true], $response->json());
    }

    public function testFailedResponse(): void
    {
        $response = new CurlResponse(httpCode: 0, body: null, error: 'Connection refused');

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->failed());
        $this->assertNull($response->json());
    }

    public function testNon200IsNotSuccess(): void
    {
        $response = new CurlResponse(httpCode: 500, body: 'error', error: null);

        $this->assertFalse($response->isSuccess());
    }

    public function testJsonReturnsNullForInvalidBody(): void
    {
        $response = new CurlResponse(httpCode: 200, body: 'not json', error: null);

        $this->assertNull($response->json());
    }
}
