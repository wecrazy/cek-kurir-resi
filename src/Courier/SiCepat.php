<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\{HistoryEntry, Receiver, Sender, ShipmentInfo, TrackingResult};

final class SiCepat extends AbstractCourier
{
    public function getName(): string { return 'SiCepat'; }
    public function getSite(): string { return 'sicepat.com'; }
    public function getSlug(): string { return 'sicepat'; }

    public function track(string $resi): string
    {
        $apiUrl = $this->env('SICEPAT_API_URL', 'http://api.sicepat.com/customer/waybill');
        $apiKey = $this->envRequired('SICEPAT_API_KEY');

        $response = $this->http->get(
            url: "{$apiUrl}?waybill={$resi}",
            headers: ["api-key: {$apiKey}"],
        );

        $data   = $response->json();
        $status = $data['sicepat']['status']['code'] ?? null;

        if ($status !== 200) {
            return TrackingResult::error($this->getName(), $this->getSite(), 'Nomor resi tidak ditemukan.')->toJson();
        }

        $result = $data['sicepat']['result'];

        $info = new ShipmentInfo(
            noAwb:         $result['waybill_number'] ?? null,
            service:       $result['service'] ?? null,
            status:        strtoupper($result['last_status']['status'] ?? ''),
            tanggalKirim:  $result['send_date'] ?? null,
            tanggalTerima: $result['POD_receiver_time'] ?? null,
            harga:         $result['totalprice'] ?? null,
            berat:         $result['weight'] ?? null,
        );

        $pengirim = new Sender(
            nama:   $result['sender'] ?? null,
            alamat: $result['sender_address'] ?? null,
        );

        // Parse receiver name
        $lastStatus     = $result['last_status'] ?? [];
        $receiverParts  = preg_split('/[\[\]]/', $lastStatus['receiver_name'] ?? '');
        $namaParts      = explode(' - ', $receiverParts[1] ?? '');

        $penerima = new Receiver(
            nama:          $result['receiver_name'] ?? null,
            namaPenerima:  reset($namaParts) ?: null,
            alamat:        $result['receiver_address'] ?? null,
        );

        $historyEntries = array_map(function (array $item): HistoryEntry {
            $tanggal = $this->formatDate($item['date_time'] ?? null) ?? '';

            if (($item['status'] ?? '') === 'DELIVERED') {
                return new HistoryEntry(
                    tanggal: $tanggal,
                    posisi:  'Diterima',
                    message: $item['receiver_name'] ?? null,
                );
            }

            $city  = $item['city'] ?? '';
            $posisi = preg_replace('/(.*)\[(.*)\](.*)/', '$2', $city);

            if (str_contains($city, 'SIGESIT')) {
                $posisi = 'Diantar';
            }

            return new HistoryEntry(
                tanggal: $tanggal,
                posisi:  $posisi,
                message: $city,
            );
        }, $result['track_history'] ?? []);

        return TrackingResult::success(
            $this->getName(), $this->getSite(), $info, $pengirim, $penerima, $historyEntries,
        )->toJson();
    }
}
