<?php

declare(strict_types=1);

namespace CekResi\DTO;

final readonly class Sender
{
    public function __construct(
        public ?string $nama = null,
        public ?string $phone = null,
        public ?string $alamat = null,
    ) {}

    public function toArray(): array
    {
        return [
            'nama'   => $this->nama,
            'phone'  => $this->phone,
            'alamat' => $this->alamat,
        ];
    }
}
