<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\{HistoryEntry, Receiver, Sender, ShipmentInfo, TrackingResult};

final class Tiki extends AbstractCourier
{
    public function getName(): string { return 'TIKI'; }
    public function getSite(): string { return 'tiki.id'; }
    public function getSlug(): string { return 'tiki'; }

    public function track(string $resi): string
    {
        $infoUrl    = $this->env('TIKI_INFO_URL', 'https://my.tiki.id/api/connote/info');
        $historyUrl = $this->env('TIKI_HISTORY_URL', 'https://my.tiki.id/api/connote/history');
        $authToken  = $this->envRequired('TIKI_AUTH_TOKEN');

        $headers = [
            "Authorization:  {$authToken}",
            'Content-Type: application/x-www-form-urlencoded',
        ];

        // Fetch info and history
        $infoResponse    = $this->http->post($infoUrl, "cnno={$resi}", $headers);
        $historyResponse = $this->http->post($historyUrl, "cnno={$resi}", $headers);

        $infoData    = $infoResponse->json();
        $historyData = $historyResponse->json();

        $infoResult = $infoData['response'][0] ?? null;
        $historyList = $historyData['response'][0]['history'] ?? [];

        if ($infoResult === null) {
            return TrackingResult::error($this->getName(), $this->getSite(), 'Nomor resi tidak ditemukan.')->toJson();
        }

        // Determine dates & status
        $firstHistory = end($historyList) ?: [];
        $lastHistory  = reset($historyList) ?: [];

        $tanggalKirim  = $firstHistory['entry_date'] ?? null;
        $statusKirim   = 'ON PROCESS';
        $tanggalTerima = null;
        $namaPenerima  = null;

        if (str_contains($lastHistory['noted'] ?? '', 'Success')) {
            $tanggalTerima = $lastHistory['entry_date'] ?? null;
            $statusKirim   = 'DELIVERED';
            $namaPenerima  = preg_replace('/(.*) RECEIVED BY: (.*)/', '$2', $lastHistory['noted'] ?? '');
        }

        $info = new ShipmentInfo(
            noAwb:         $infoResult['cnno'] ?? null,
            service:       $infoResult['product'] ?? null,
            status:        $statusKirim,
            tanggalKirim:  $this->formatDate($tanggalKirim),
            tanggalTerima: $this->formatDate($tanggalTerima),
            harga:         $infoResult['total_fee'] ?? null,
            berat:         $infoResult['weight'] ?? null,
        );

        $pengirim = new Sender(
            nama:   $infoResult['consignor_name'] ?? null,
            alamat: $infoResult['consignor_address'] ?? null,
        );

        $penerima = new Receiver(
            nama:          $infoResult['consignee_name'] ?? null,
            namaPenerima:  $namaPenerima,
            alamat:        $infoResult['consignee_address'] ?? null,
        );

        $reversedHistory = array_reverse($historyList);
        $historyEntries  = array_map(function (array $item): HistoryEntry {
            $noted = $item['noted'] ?? '';

            return new HistoryEntry(
                tanggal: $this->formatDate($item['entry_date'] ?? null) ?? '',
                posisi:  str_contains($noted, 'Success') ? 'DITERIMA' : ($item['entry_name'] ?? null),
                message: $noted,
            );
        }, $reversedHistory);

        return TrackingResult::success(
            $this->getName(), $this->getSite(), $info, $pengirim, $penerima, $historyEntries,
        )->toJson();
    }
}
