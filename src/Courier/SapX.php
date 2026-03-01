<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\DTO\SimpleTrackingResult;

final class SapX extends AbstractCourier
{
    public function getName(): string { return 'SAP Express'; }
    public function getSite(): string { return 'sapx.id'; }
    public function getSlug(): string { return 'sapx'; }

    public function track(string $resi): string
    {
        $apiUrl  = $this->env('SAPX_API_URL', 'https://track.coresyssap.com/v2/shipment/tracking');
        $apiKey  = $this->envRequired('SAPX_API_KEY');
        $apiCode = $this->env('SAPX_API_CODE', 'CSNA');

        $response = $this->http->get(
            url: "{$apiUrl}?awb_no={$resi}",
            headers: [
                "api_key: {$apiKey}",
                "api_code: {$apiCode}",
            ],
        );

        $json = $response->json();
        $items = $json['data'] ?? [];
        $data  = [];

        foreach ($items as $item) {
            $data[] = [
                'reference_no'  => $item['reference_no'] ?? null,
                'receiver_name' => $item['receiver_name'] ?? null,
                'status'        => $item['rowstate_name'] ?? null,
                'detailStatus'  => $item['rowstate_web'] ?? null,
                'location'      => $item['current_branch_name'] ?? null,
                'create_date'   => $item['create_date'] ?? null,
            ];
        }

        return (new SimpleTrackingResult(noResi: $resi, data: $data))->toJson();
    }
}
