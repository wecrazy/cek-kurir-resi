<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\{HistoryEntry, Receiver, Sender, ShipmentInfo, TrackingResult};

final class NinjaXpress extends AbstractCourier
{
    public function getName(): string { return 'NinjaXpress'; }
    public function getSite(): string { return 'www.ninjaxpress.co'; }
    public function getSlug(): string { return 'ninja'; }

    public function track(string $resi): string
    {
        $apiUrl = $this->env('NINJA_API_URL', 'https://api.ninjavan.co/id/shipperpanel/app/tracking');

        $response = $this->http->get(
            url: "{$apiUrl}?id={$resi}",
            headers: ['User-Agent: okhttp/3.4.1'],
        );

        $data   = $response->json();
        $order  = $data['orders'][0] ?? null;
        $events = $order['events'] ?? [];

        $firstEvent    = reset($events) ?: [];
        $tanggalKirim  = isset($firstEvent['time']) ? (int) ($firstEvent['time'] / 1000) : null;

        if ($tanggalKirim === null) {
            return TrackingResult::error($this->getName(), $this->getSite(), 'Nomor resi tidak ditemukan.')->toJson();
        }

        $lastEvent     = end($events) ?: [];
        $tanggalTerima = str_contains($lastEvent['description'] ?? '', 'berhasil dikirimkan')
            ? (int) ($lastEvent['time'] / 1000)
            : null;

        $status = match ($order['status'] ?? '') {
            'Completed' => 'DELIVERED',
            default     => strtoupper($order['status'] ?? 'UNKNOWN'),
        };

        $info = new ShipmentInfo(
            noAwb:         $order['tracking_id'] ?? null,
            service:       $order['service_type'] ?? null,
            status:        $status,
            tanggalKirim:  $this->formatDate($tanggalKirim),
            tanggalTerima: $this->formatDate($tanggalTerima),
        );

        $pengirim = new Sender(nama: $order['from_name'] ?? null);

        $penerima = new Receiver(
            namaPenerima: $order['transactions'][1]['signature']['name'] ?? null,
            alamat:       $order['to_city'] ?? null,
        );

        $historyEntries = array_map(function (array $event): HistoryEntry {
            $tanggal     = $this->formatDate((int) (($event['time'] ?? 0) / 1000)) ?? '';
            $description = $event['description'] ?? '';

            if (str_contains($description, 'berhasil dikirimkan')) {
                return new HistoryEntry(tanggal: $tanggal, posisi: 'DITERIMA', message: $description);
            }

            if (str_contains($description, ' - ')) {
                $parts = preg_split('/[\[\]]/', $description);
                $posisi = preg_replace('/(.*) - (.*)/', '$2', $description);
                $message = rtrim(str_replace(' AT', '', $parts[0] ?? ''));
                return new HistoryEntry(tanggal: $tanggal, posisi: $posisi, message: $message);
            }

            return new HistoryEntry(tanggal: $tanggal, posisi: null, message: $description);
        }, $events);

        return TrackingResult::success(
            $this->getName(), $this->getSite(), $info, $pengirim, $penerima, $historyEntries,
        )->toJson();
    }
}
