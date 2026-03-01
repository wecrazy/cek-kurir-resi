<?php

declare(strict_types=1);

namespace CekResi\DTO;

final readonly class ShipmentInfo
{
    public function __construct(
        public ?string $noAwb = null,
        public ?string $service = null,
        public ?string $status = null,
        public ?string $tanggalKirim = null,
        public ?string $tanggalTerima = null,
        public int|string|null $harga = null,
        public int|float|string|null $berat = null,
        public ?string $catatan = null,
    ) {}

    public function toArray(): array
    {
        return [
            'no_awb'         => $this->noAwb,
            'service'        => $this->service,
            'status'         => $this->status,
            'tanggal_kirim'  => $this->tanggalKirim,
            'tanggal_terima' => $this->tanggalTerima,
            'harga'          => $this->harga,
            'berat'          => $this->berat,
            'catatan'        => $this->catatan,
        ];
    }
}
