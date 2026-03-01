<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\Config\Config;
use CekResi\Http\CurlClient;

final class CourierFactory
{
    /** @var array<string, class-string<CourierInterface>> */
    private const COURIER_MAP = [
        'anteraja'   => AnterAja::class,
        'sicepat'    => SiCepat::class,
        'pos'        => PosIndonesia::class,
        'ninja'      => NinjaXpress::class,
        'tiki'       => Tiki::class,
        'linitrans'  => LiniTrans::class,
        'lionparcel' => LionParcel::class,
        'sapx'       => SapX::class,
    ];

    public function __construct(
        private readonly Config $config,
        private readonly ?CurlClient $http = null,
    ) {}

    public function create(string $slug): ?CourierInterface
    {
        $class = self::COURIER_MAP[strtolower($slug)] ?? null;

        if ($class === null) {
            return null;
        }

        return new $class($this->config, $this->http);
    }

    /** @return list<string> */
    public function supportedSlugs(): array
    {
        return array_keys(self::COURIER_MAP);
    }

    public function isSupported(string $slug): bool
    {
        return isset(self::COURIER_MAP[strtolower($slug)]);
    }
}
