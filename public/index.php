<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CekResi\Config\Config;
use CekResi\Courier\CourierFactory;

// ── Bootstrap ────────────────────────────────────────────
$config = Config::load(dirname(__DIR__));

date_default_timezone_set($config->getTimezone());

if (!$config->isDebug()) {
    error_reporting(0);
}

header('Content-Type: application/json; charset=utf-8');

// ── Input ────────────────────────────────────────────────
$kurir = strtolower(trim($_GET['kurir'] ?? ''));
$resi  = trim($_GET['resi'] ?? '');

// ── Validation ───────────────────────────────────────────
$factory = new CourierFactory($config);

if ($kurir === '' && $resi === '') {
    echo json_encode([
        'name'    => null,
        'site'    => null,
        'error'   => true,
        'message' => 'Anda belum memasukkan jasa pengiriman & resi!',
    ]);
    exit;
}

if ($kurir === '' && $resi !== '') {
    echo json_encode([
        'name'    => null,
        'site'    => null,
        'error'   => true,
        'message' => 'Anda hanya memasukkan resi saja, mohon tambahkan jasa pengiriman!',
    ]);
    exit;
}

if ($kurir !== '' && $resi === '') {
    echo json_encode([
        'name'    => null,
        'site'    => null,
        'error'   => true,
        'message' => 'Anda hanya memasukkan jasa pengiriman saja, mohon tambahkan resi!',
    ]);
    exit;
}

if (!$factory->isSupported($kurir)) {
    echo json_encode([
        'name'    => null,
        'site'    => null,
        'error'   => true,
        'message' => 'Jasa pengiriman belum didukung!',
    ]);
    exit;
}

// ── Track ────────────────────────────────────────────────
try {
    $courier = $factory->create($kurir);
    echo $courier->track($resi);
} catch (\Throwable $e) {
    $response = [
        'name'    => null,
        'site'    => null,
        'error'   => true,
        'message' => 'Terjadi kesalahan internal.',
    ];

    if ($config->isDebug()) {
        $response['debug'] = $e->getMessage();
    }

    echo json_encode($response);
}
