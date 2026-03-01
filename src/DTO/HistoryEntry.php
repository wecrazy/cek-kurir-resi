<?php

declare(strict_types=1);

namespace CekResi\DTO;

final readonly class HistoryEntry
{
    public function __construct(
        public string $tanggal,
        public ?string $posisi = null,
        public ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'tanggal' => $this->tanggal,
            'posisi'  => $this->posisi,
            'message' => $this->message,
        ];
    }
}
