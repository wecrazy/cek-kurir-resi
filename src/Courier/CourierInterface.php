<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\Config\Config;

interface CourierInterface
{
    /** Get the courier display name */
    public function getName(): string;

    /** Get the courier website */
    public function getSite(): string;

    /** Get the lowercase courier slug */
    public function getSlug(): string;

    /** Track a shipment and return JSON string */
    public function track(string $resi): string;
}
