<?php

declare(strict_types=1);

namespace CekResi\Tests\Unit\DTO;

use CekResi\DTO\{HistoryEntry, Receiver, Sender, ShipmentInfo, TrackingResult};
use PHPUnit\Framework\TestCase;

final class TrackingResultTest extends TestCase
{
    public function testErrorResultToArray(): void
    {
        $result = TrackingResult::error('TestCourier', 'test.com', 'Not found');
        $array  = $result->toArray();

        $this->assertSame('TestCourier', $array['name']);
        $this->assertSame('test.com', $array['site']);
        $this->assertTrue($array['error']);
        $this->assertSame('Not found', $array['message']);
        $this->assertArrayNotHasKey('info', $array);
    }

    public function testSuccessResultToArray(): void
    {
        $info     = new ShipmentInfo(noAwb: 'AWB123', status: 'DELIVERED');
        $sender   = new Sender(nama: 'John');
        $receiver = new Receiver(nama: 'Jane', namaPenerima: 'Jane');
        $history  = [new HistoryEntry(tanggal: '01-01-2026 10:00', posisi: 'Jakarta', message: 'Picked up')];

        $result = TrackingResult::success('TestCourier', 'test.com', $info, $sender, $receiver, $history);
        $array  = $result->toArray();

        $this->assertFalse($array['error']);
        $this->assertSame('success', $array['message']);
        $this->assertSame('AWB123', $array['info']['no_awb']);
        $this->assertSame('John', $array['pengirim']['nama']);
        $this->assertSame('Jane', $array['penerima']['nama']);
        $this->assertCount(1, $array['history']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $result = TrackingResult::error('Test', 'test.com', 'Error message');
        $json   = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['error']);
    }
}
