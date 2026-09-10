<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Google Search Console API — real search performance data (clicks, impressions, CTR,
 * position), as opposed to the Page Indexing report, which Google does not expose via any API.
 *
 * Auth follows the same service-account JWT-bearer flow as _getAccessToken() in app/helpers.php
 * (used there for Firebase), just against the Search Console read-only scope and a different key
 * file — the two credentials are unrelated and must not share a file.
 */
class SearchConsoleService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
    private const API_BASE = 'https://searchconsole.googleapis.com/webmasters/v3';

    private function credentials(): array
    {
        $path = config('services.search_console.credentials_path');

        if (!$path || !is_file($path)) {
            throw new RuntimeException("Search Console credentials file not found at: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);

        if (empty($data['client_email']) || empty($data['private_key'])) {
            throw new RuntimeException('Search Console credentials file is missing client_email/private_key.');
        }

        return $data;
    }

    /** Fresh access token per call — the sync command runs once a day, so caching isn't worth the complexity. */
    private function accessToken(): string
    {
        $account = $this->credentials();
        $now = time();

        $jwt = JWT::encode([
            'iss'   => $account['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::TOKEN_URL,
            'exp'   => $now + 3600,
            'iat'   => $now,
        ], $account['private_key'], 'RS256');

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$response->ok() || !$response->json('access_token')) {
            Log::error('SearchConsoleService: token exchange failed', ['body' => $response->body()]);
            throw new RuntimeException('Search Console auth failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * One page of searchAnalytics.query results.
     *
     * @param string[] $dimensions e.g. ['date'], ['page'], ['query']
     */
    public function query(string $startDate, string $endDate, array $dimensions, int $rowLimit = 1000, int $startRow = 0): array
    {
        $siteUrl = config('services.search_console.site_url');
        $endpoint = self::API_BASE . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query';

        $response = Http::withToken($this->accessToken())
            ->post($endpoint, [
                'startDate'  => $startDate,
                'endDate'    => $endDate,
                'dimensions' => $dimensions,
                'rowLimit'   => $rowLimit,
                'startRow'   => $startRow,
            ]);

        if (!$response->ok()) {
            Log::error('SearchConsoleService: query failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Search Console query failed: HTTP ' . $response->status());
        }

        return $response->json('rows', []);
    }
}
