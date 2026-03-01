<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\{HistoryEntry, Receiver, Sender, ShipmentInfo, TrackingResult};

final class PosIndonesia extends AbstractCourier
{
    public function getName(): string { return 'Pos Indonesia'; }
    public function getSite(): string { return 'www.posindonesia.co.id'; }
    public function getSlug(): string { return 'pos'; }

    public function track(string $resi): string
    {
        $apiUrl = $this->env('POS_API_URL', 'https://order.posindonesia.co.id/api/lacak');

        $response = $this->http->post(
            url: $apiUrl,
            body: json_encode(['barcode' => $resi]),
            headers: ['content-type: application/json'],
        );

        $data = $response->json();

        if (($data['errors']['global'] ?? null) === 'Data dengan barcode tersebut tidak ditemukan') {
            return TrackingResult::error($this->getName(), $this->getSite(), 'Nomor resi tidak ditemukan.')->toJson();
        }

        $results    = $data['result'] ?? [];
        $firstEvent = reset($results) ?: [];
        $lastEvent  = end($results) ?: [];

        // Determine delivery status
        $statusKirim   = 'ON PROCESS';
        $tanggalTerima = null;
        $namaPenerima  = null;

        if (str_contains($lastEvent['description'] ?? '', 'Diterima')) {
            $statusKirim   = 'DELIVERED';
            $tanggalTerima = $lastEvent['eventDate'] ?? null;
            $namaPenerima  = preg_replace('/(.*)PENERIMA \/ KETERANGAN : (.*)/', '$2', $lastEvent['description'] ?? '');
        }

        // Parse first event description
        $ledakInfo = explode(';', $firstEvent['description'] ?? '');

        $info = new ShipmentInfo(
            noAwb:         $resi,
            service:       preg_replace('/(.*)LAYANAN :(.*)/', '$2', $ledakInfo[0] ?? ''),
            status:        $statusKirim,
            tanggalKirim:  $this->formatDate($firstEvent['eventDate'] ?? null),
            tanggalTerima: $this->formatDate($tanggalTerima),
        );

        $pengirim = new Sender(
            nama:   preg_replace('/(.*)PENGIRIM : (.*)/', '$2', $ledakInfo[1] ?? ''),
            phone:  $ledakInfo[3] ?? null,
            alamat: ($ledakInfo[2] ?? '') . ', ' . ($ledakInfo[4] ?? ''),
        );

        $penerima = new Receiver(
            nama:          preg_replace('/(.*)PENERIMA : (.*)/', '$2', $ledakInfo[7] ?? ''),
            namaPenerima:  $namaPenerima,
            phone:         $ledakInfo[9] ?? null,
            alamat:        ($ledakInfo[8] ?? '') . ', ' . ($ledakInfo[10] ?? ''),
        );

        $historyEntries = [];
        foreach ($results as $item) {
            $eventName = $item['eventName'] ?? '';
            $date      = $this->formatDate($item['eventDate'] ?? null) ?? '';
            $office    = $item['officeName'] ?? '';
            $desc      = $item['description'] ?? '';

            $entry = match ($eventName) {
                'POSTING LOKET' => new HistoryEntry(
                    tanggal: $date,
                    posisi:  $office,
                    message: "Penerimaan di loket {$office}",
                ),
                'MANIFEST SERAH' => new HistoryEntry(
                    tanggal: $date,
                    posisi:  $office,
                    message: 'Diteruskan ke Hub ' . preg_replace('/(.*)KANTOR TUJUAN : (.*)/', '$2', $desc),
                ),
                'MANIFEST TERIMA' => new HistoryEntry(
                    tanggal: $date,
                    posisi:  $office,
                    message: "Tiba di Hub {$office}",
                ),
                'PROSES ANTAR' => new HistoryEntry(
                    tanggal: $date,
                    posisi:  $office,
                    message: "Proses antar di {$office}",
                ),
                'SELESAI ANTAR' => new HistoryEntry(
                    tanggal: $date,
                    posisi:  $office,
                    message: $this->parseSelesaiAntar($desc),
                ),
                default => null,
            };

            if ($entry !== null) {
                $historyEntries[] = $entry;
            }
        }

        return TrackingResult::success(
            $this->getName(), $this->getSite(), $info, $pengirim, $penerima, $historyEntries,
        )->toJson();
    }

    private function parseSelesaiAntar(string $description): string
    {
        if (str_contains($description, 'Antar Ulang')) {
            $keterangan = preg_replace('/(.*)KETERANGAN : (.*)/', '$2', $description);
            return "Gagal antar - ({$keterangan})";
        }

        $namaPenerima = preg_replace('/(.*)PENERIMA \/ KETERANGAN : (.*)/', '$2', $description);
        return "Selesai antar. ({$namaPenerima})";
    }
}
