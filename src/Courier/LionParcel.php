<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\SimpleTrackingResult;

final class LionParcel extends AbstractCourier
{
    public function getName(): string { return 'Lion Parcel'; }
    public function getSite(): string { return 'thelionparcel.com'; }
    public function getSlug(): string { return 'lionparcel'; }

    public function track(string $resi): string
    {
        $apiUrl = $this->env('LIONPARCEL_API_URL', 'https://api-internal-web.thelionparcel.com/v2/track/data');
        $token  = $this->envRequired('LIONPARCEL_TOKEN');

        $response = $this->http->get(
            url: "{$apiUrl}?q={$resi}",
            headers: ["Authorization: Bearer {$token}"],
        );

        if ($response->isSuccess()) {
            $json = $response->json();
            if (!empty($json['data'][0]['histories'] ?? [])) {
                return $this->buildResult($json, $resi)->toJson();
            }
        }

        // Fallback API
        return $this->fallbackTrack($resi);
    }

    private function fallbackTrack(string $resi): string
    {
        $fallbackUrl   = $this->env('LIONPARCEL_FALLBACK_URL', 'https://api-middleware.lionparcel.com/v3/stt/track');
        $fallbackToken = $this->env('LIONPARCEL_FALLBACK_TOKEN', '');

        $response = $this->http->get(
            url: "{$fallbackUrl}?q={$resi}",
            headers: ["Authorization: Bearer {$fallbackToken}"],
        );

        $json = $response->json();

        return $this->buildResult($json, $resi, withOriginDest: true)->toJson();
    }

    private function buildResult(?array $json, string $resi, bool $withOriginDest = false): SimpleTrackingResult
    {
        $histories = $json['data'][0]['histories'] ?? [];
        $data      = [];

        foreach ($histories as $history) {
            $data[] = [
                'reference_no'  => '-',
                'receiver_name' => '-',
                'status'        => $history['status'] ?? null,
                'detailStatus'  => $history['status_label'] ?? null,
                'location'      => $history['location'] ?? null,
                'create_date'   => $history['status_date'] ?? null,
            ];
        }

        return new SimpleTrackingResult(
            noResi:      $resi,
            data:        $data,
            origin:      $withOriginDest ? ($json['data'][0]['origin'] ?? null) : null,
            destination: $withOriginDest ? ($json['data'][0]['destination'] ?? null) : null,
        );
    }
}
