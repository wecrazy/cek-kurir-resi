<?php

declare(strict_types=1);

namespace CekResi\Tests\Unit\Courier;

use CekResi\Courier\CourierFactory;
use CekResi\Config\Config;
use PHPUnit\Framework\TestCase;

final class CourierFactoryTest extends TestCase
{
    private CourierFactory $factory;

    protected function setUp(): void
    {
        Config::reset();
        $config = Config::load(dirname(__DIR__, 3));
        $this->factory = new CourierFactory($config);
    }

    protected function tearDown(): void
    {
        Config::reset();
    }

    public function testSupportedSlugsReturnsAllCouriers(): void
    {
        $slugs = $this->factory->supportedSlugs();

        $this->assertContains('anteraja', $slugs);
        $this->assertContains('sicepat', $slugs);
        $this->assertContains('pos', $slugs);
        $this->assertContains('ninja', $slugs);
        $this->assertContains('tiki', $slugs);
        $this->assertContains('linitrans', $slugs);
        $this->assertContains('lionparcel', $slugs);
        $this->assertContains('sapx', $slugs);
        $this->assertCount(8, $slugs);
    }

    public function testIsSupportedReturnsTrueForValidSlug(): void
    {
        $this->assertTrue($this->factory->isSupported('sicepat'));
        $this->assertTrue($this->factory->isSupported('ANTERAJA'));
    }

    public function testIsSupportedReturnsFalseForInvalidSlug(): void
    {
        $this->assertFalse($this->factory->isSupported('fake'));
        $this->assertFalse($this->factory->isSupported(''));
    }

    public function testCreateReturnsNullForUnknownSlug(): void
    {
        $this->assertNull($this->factory->create('nonexistent'));
    }

    public function testCreateReturnsCourierInstance(): void
    {
        $courier = $this->factory->create('sicepat');

        $this->assertNotNull($courier);
        $this->assertSame('SiCepat', $courier->getName());
        $this->assertSame('sicepat', $courier->getSlug());
    }
}
