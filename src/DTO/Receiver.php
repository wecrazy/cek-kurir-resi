<?php

declare(strict_types=1);

namespace CekResi\DTO;

final readonly class Receiver
{
    public function __construct(
        public ?string $nama = null,
        public ?string $namaPenerima = null,
        public ?string $phone = null,
        public ?string $alamat = null,
    ) {}

    public function toArray(): array
    {
        return [
            'nama'           => $this->nama,
            'nama_penerima'  => $this->namaPenerima,
            'phone'          => $this->phone,
            'alamat'         => $this->alamat,
        ];
    }
}
