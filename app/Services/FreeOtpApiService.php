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
            $response = Http::timeout(10)->get("{$this->baseUrl}/list/{$country}");

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

            $params = [];
            if (!empty($match)) {
                $params['match'] = $match;
            }
            if (!empty($ago)) {
                $params['ago'] = $ago;
            }

            $url = "{$this->baseUrl}/{$country}/{$phone}";
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
