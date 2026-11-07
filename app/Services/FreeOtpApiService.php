<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreeOtpApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('sms.free_otp.base_url', 'https://otp-api.shelex.dev/api'), '/');
    }

    /**
     * Get list of countries available.
     * GET https://otp-api.shelex.dev/api/countries
     *
     * @return array
     */
    public function getCountries(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/countries");

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning("[FreeOtpApiService] Failed to fetch countries: " . $response->status());
            return [];
        } catch (Exception $e) {
            Log::error("[FreeOtpApiService] Exception fetching countries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get list of phone numbers for a specific country.
     * GET https://otp-api.shelex.dev/api/list/{{country}}
     *
     * @param string $country
     * @return array
     */
    public function getPhoneNumbers(string $country): array
    {
        try {
            $country = strtolower(trim($country));
            if (!preg_match('/^[a-z0-9_-]{2,20}$/', $country)) {
                Log::warning("[FreeOtpApiService] Rejected invalid country parameter: " . substr($country, 0, 50));
                return [];
            }

            $safeCountry = urlencode($country);
            $response = Http::timeout(10)->get("{$this->baseUrl}/list/{$safeCountry}");

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning("[FreeOtpApiService] Failed to fetch phone numbers for {$country}: " . $response->status());
            return [];
        } catch (Exception $e) {
            Log::error("[FreeOtpApiService] Exception fetching numbers for {$country}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get OTP / SMS verification code sent to a specific number.
     * GET https://otp-api.shelex.dev/api/{{country}}/{{phone}}?match={{substring}}&ago={{ago}}
     *
     * @param string $country
     * @param string $phone
     * @param string|null $match Substring or brand to match (e.g., 'CLINICSYNC')
     * @param int|null $ago Time window in minutes/seconds
     * @return array|null
     */
    public function getOtpCode(string $country, string $phone, ?string $match = null, ?int $ago = null): ?array
    {
        try {
            $country = strtolower(trim($country));
            $phone = preg_replace('/[^\d+]/', '', trim($phone));

            if (!preg_match('/^[a-z0-9_-]{2,20}$/', $country) || !preg_match('/^\+?[0-9]{5,20}$/', $phone)) {
                Log::warning("[FreeOtpApiService] Rejected invalid country or phone format.");
                return null;
            }

            $params = [];
            if (!empty($match)) {
                $params['match'] = $match;
            }
            if (!empty($ago)) {
                $params['ago'] = $ago;
            }

            $safeCountry = urlencode($country);
            $safePhone = urlencode($phone);
            $url = "{$this->baseUrl}/{$safeCountry}/{$safePhone}";
            $response = Http::timeout(10)->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("[FreeOtpApiService] Failed to fetch OTP for {$phone} ({$country}): " . $response->status());
            return null;
        } catch (Exception $e) {
            Log::error("[FreeOtpApiService] Exception fetching OTP for {$phone}: " . $e->getMessage());
            return null;
        }
    }
}
