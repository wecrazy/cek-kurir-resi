<?php

declare(strict_types=1);

namespace CekResi\DTO;

/**
 * Simplified result format used by LiniTrans, LionParcel, SapX couriers.
 */
final class SimpleTrackingResult
{
    /** @param array<int, array<string, mixed>> $data */
    public function __construct(
        public readonly string $noResi,
        public readonly array $data = [],
        public readonly ?string $origin = null,
        public readonly ?string $destination = null,
        public readonly ?string $status = null,
        public readonly ?string $statusMessage = null,
    ) {}

    public static function error(string $status, string $msg): self
    {
        return new self(
            noResi: '',
            status: $status,
            statusMessage: $msg,
        );
    }

    public function toArray(): array
    {
        $result = ['noResi' => $this->noResi];

        if ($this->status !== null) {
            $result['status'] = $this->status;
            $result['msg'] = $this->statusMessage;
            return $result;
        }

        if ($this->origin !== null) {
            $result['origin'] = $this->origin;
        }
        if ($this->destination !== null) {
            $result['destination'] = $this->destination;
        }

        $result['data'] = $this->data;

        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
