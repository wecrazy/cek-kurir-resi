<?php

declare(strict_types=1);

namespace CekResi\Courier;

use CekResi\Config\Config;
use CekResi\DTO\SimpleTrackingResult;
use CekResi\Http\CurlClient;

final class LiniTrans extends AbstractCourier
{
    private readonly string $iniFilePath;

    public function __construct(Config $config, ?CurlClient $http = null)
    {
        parent::__construct($config, $http);
        $this->iniFilePath = dirname(__DIR__, 2) . '/storage/linitrans.ini';
    }

    public function getName(): string { return 'LiniTrans'; }
    public function getSite(): string { return 'linitransslogistics.com'; }
    public function getSlug(): string { return 'linitrans'; }

    public function track(string $resi): string
    {
        $traceApi = $this->env('LINITRANS_TRACE_API', 'https://linitransslogistics.com/wp-content/plugins/lds-track-and-trace/api-helper/lds.api.php');
        $apiUrl   = $this->env('LINITRANS_API_URL');
        $apiToken = $this->env('LINITRANS_API_TOKEN');

        // Try from .env config first, then fall back to ini file
        if (empty($apiUrl) || empty($apiToken)) {
            if (file_exists($this->iniFilePath)) {
                $iniConfig = parse_ini_file($this->iniFilePath);
                $apiUrl    = $iniConfig['api_url'] ?? null;
                $apiToken  = $iniConfig['api_token'] ?? null;
            }
        }

        if (!empty($apiUrl) && !empty($apiToken)) {
            $result = $this->fetchTracking($traceApi, $apiUrl, $apiToken, $resi);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback: scrape website for new credentials
        return $this->handleFallback($traceApi, $resi);
    }

    private function fetchTracking(string $traceApi, string $apiUrl, string $apiToken, string $resi): ?string
    {
        $response = $this->http->postForm($traceApi, [
            'api_url'    => $apiUrl,
            'api_token'  => $apiToken,
            'airwaybill' => $resi,
            'ajax'       => 1,
        ]);

        if (!$response->isSuccess()) {
            return null;
        }

        $json = $response->json();
        if ($json === null || empty($json['data'][0]['detail'] ?? [])) {
            return null;
        }

        return $this->buildResult($json, $resi)->toJson();
    }

    private function handleFallback(string $traceApi, string $resi): string
    {
        $webUrl = $this->env('LINITRANS_WEB_URL', 'https://linitransslogistics.com/track-trace');
        $html   = @file_get_contents($webUrl);

        if ($html === false) {
            return SimpleTrackingResult::error('Error', 'Failed to retrieve HTML content.')->toJson();
        }

        $dom = new \simple_html_dom();
        $dom->load($html);

        $urlInput   = $dom->find('input[id=ldstrackandtrace_c_url_tracking]', 0);
        $tokenInput = $dom->find('input[id=ldstrackandtrace_c_api_token]', 0);

        if (!$urlInput || !$tokenInput) {
            $dom->clear();
            return SimpleTrackingResult::error('Error', 'Inputs not found.')->toJson();
        }

        $apiUrl   = $urlInput->value;
        $apiToken = $tokenInput->value;

        $response = $this->http->postForm($traceApi, [
            'api_url'    => $apiUrl,
            'api_token'  => $apiToken,
            'airwaybill' => $resi,
            'ajax'       => 1,
        ]);

        $dom->clear();

        if ($response->failed()) {
            return SimpleTrackingResult::error('Error', "Request failed: {$response->error}")->toJson();
        }

        // Persist updated credentials
        $this->saveIniConfig($apiUrl, $apiToken);

        $json = $response->json();
        return $this->buildResult($json, $resi)->toJson();
    }

    private function buildResult(array $json, string $resi): SimpleTrackingResult
    {
        $details = $json['data'][0]['detail'] ?? [];
        $data    = [];

        for ($i = count($details) - 1; $i >= 0; $i--) {
            $d = $details[$i];
            $data[] = [
                'reference_no'  => $d['manifest_no'] ?? null,
                'receiver_name' => $d['consignee_name'] ?? null,
                'status'        => $d['status_name'] ?? null,
                'detailStatus'  => $d['description'] ?? null,
                'location'      => $d['kabupaten_name'] ?? null,
                'create_date'   => $d['date'] ?? null,
            ];
        }

        return new SimpleTrackingResult(noResi: $resi, data: $data);
    }

    private function saveIniConfig(string $apiUrl, string $apiToken): void
    {
        $dir = dirname($this->iniFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->iniFilePath, "api_url={$apiUrl}\napi_token={$apiToken}");
    }
}
