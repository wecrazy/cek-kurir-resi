<?php

declare(strict_types=1);

namespace CekResi\DTO;

final class TrackingResult
{
    /** @param HistoryEntry[] $history */
    public function __construct(
        public readonly string $courierName,
        public readonly string $courierSite,
        public readonly bool $error,
        public readonly string $message,
        public readonly ?ShipmentInfo $info = null,
        public readonly ?Sender $pengirim = null,
        public readonly ?Receiver $penerima = null,
        public readonly array $history = [],
    ) {}

    public static function error(string $courierName, string $courierSite, string $message): self
    {
        return new self(
            courierName: $courierName,
            courierSite: $courierSite,
            error: true,
            message: $message,
        );
    }

    public static function success(
        string $courierName,
        string $courierSite,
        ShipmentInfo $info,
        Sender $pengirim,
        Receiver $penerima,
        array $history,
    ): self {
        return new self(
            courierName: $courierName,
            courierSite: $courierSite,
            error: false,
            message: 'success',
            info: $info,
            pengirim: $pengirim,
            penerima: $penerima,
            history: $history,
        );
    }

    public function toArray(): array
    {
        $result = [
            'name'    => $this->courierName,
            'site'    => $this->courierSite,
            'error'   => $this->error,
            'message' => $this->message,
        ];

        if (!$this->error) {
            $result['info']      = $this->info?->toArray();
            $result['pengirim']  = $this->pengirim?->toArray();
            $result['penerima']  = $this->penerima?->toArray();
            $result['history']   = array_map(
                fn(HistoryEntry $entry) => $entry->toArray(),
                $this->history,
            );
        }

        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
