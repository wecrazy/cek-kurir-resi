<?php

declare(strict_types=1);

namespace CekResi\Tests\Unit\Config;

use CekResi\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::reset();
    }

    public function testLoadReturnsConfigInstance(): void
    {
        $config = Config::load(dirname(__DIR__, 3));

        $this->assertInstanceOf(Config::class, $config);
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $config = Config::load(dirname(__DIR__, 3));

        $this->assertNull($config->get('NON_EXISTENT_KEY'));
        $this->assertSame('fallback', $config->get('NON_EXISTENT_KEY', 'fallback'));
    }

    public function testGetTimezoneReturnsConfiguredValue(): void
    {
        $config = Config::load(dirname(__DIR__, 3));

        $this->assertSame('Asia/Jakarta', $config->getTimezone());
    }

    public function testIsDebugReturnsFalseByDefault(): void
    {
        $config = Config::load(dirname(__DIR__, 3));

        $this->assertFalse($config->isDebug());
    }
}
