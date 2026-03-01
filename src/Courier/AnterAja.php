<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\{HistoryEntry, Receiver, Sender, ShipmentInfo, TrackingResult};
use CekResi\Http\HttpMethod;

final class AnterAja extends AbstractCourier
{
    public function getName(): string { return 'AnterAja'; }
    public function getSite(): string { return 'anteraja.id'; }
    public function getSlug(): string { return 'anteraja'; }

    public function track(string $resi): string
    {
        $apiUrl = $this->env('ANTERAJA_API_URL', 'https://api.anteraja.id/order/tracking');
        $mv     = $this->env('ANTERAJA_MV', '1.2');
        $source = $this->env('ANTERAJA_SOURCE', 'aca_android');

        $response = $this->http->post(
            url: $apiUrl,
            body: json_encode([['codes' => $resi]]),
            headers: [
                "mv: {$mv}",
                "source: {$source}",
                'content-type: application/json; charset=UTF-8',
                'user-agent: okhttp/3.10.0',
            ],
        );

        $data = $response->json();

        if (($data['status'] ?? null) !== 200) {
            return TrackingResult::error($this->getName(), $this->getSite(), 'Nomor resi tidak ditemukan.')->toJson();
        }

        $content = $data['content'][0] ?? [];
        $detail  = $content['detail'] ?? [];
        $history = $content['history'] ?? [];

        // Dates
        $firstEvent = end($history) ?: [];
        $lastEvent  = reset($history) ?: [];

        $tanggalKirim  = $this->formatDate($firstEvent['timestamp'] ?? null);
        $tanggalTerima = str_contains($lastEvent['message']['id'] ?? '', 'Delivery sukses')
            ? $this->formatDate($lastEvent['timestamp'] ?? null)
            : null;

        $status = ($detail['final_status'] ?? 0) == 250 ? 'DELIVERED' : 'ON PROCESS';

        $info = new ShipmentInfo(
            noAwb:         $content['awb'] ?? null,
            service:       $detail['service_code'] ?? null,
            status:        $status,
            tanggalKirim:  $tanggalKirim,
            tanggalTerima: $tanggalTerima,
            harga:         $detail['actual_amount'] ?? null,
            berat:         $detail['weight'] ?? null,
            catatan:       $content['items'][0]['name'] ?? null,
        );

        $pengirim = new Sender(nama: $detail['sender']['name'] ?? null);

        $penerima = new Receiver(
            nama:          $detail['receiver']['name'] ?? null,
            namaPenerima:  $detail['actual_receiver'] ?? null,
        );

        $reversedHistory = array_reverse($history);
        $historyEntries  = array_map(function (array $item): HistoryEntry {
            $msg       = $item['message']['id'] ?? '';
            $delivered = (bool) preg_match('/Delivery sukses/', $msg);

            return new HistoryEntry(
                tanggal: $this->formatDate($item['timestamp'] ?? null) ?? '',
                posisi:  $delivered ? 'Diterima' : null,
                message: $msg,
            );
        }, $reversedHistory);

        return TrackingResult::success(
            $this->getName(), $this->getSite(), $info, $pengirim, $penerima, $historyEntries,
        )->toJson();
    }
}
