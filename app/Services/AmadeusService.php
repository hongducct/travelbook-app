<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AmadeusService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.amadeus.base_url', 'https://test.api.amadeus.com');
        $this->apiKey = config('services.amadeus.api_key');
        $this->apiSecret = config('services.amadeus.api_secret');
    }

    /**
     * Get access token from Amadeus
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('amadeus_access_token', 1500, function () {
            $response = Http::asForm()->post($this->baseUrl . '/v1/security/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->apiSecret
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            throw new \Exception('Failed to get Amadeus access token');
        });
    }

    /**
     * Search for flights
     */
    public function searchFlights(array $params): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl . '/v2/shopping/flight-offers', $params);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'] ?? [];
        }

        throw new \Exception('Flight search failed: ' . $response->body());
    }

    /**
     * Search for hotels
     */
    public function searchHotels(array $params): array
    {
        $token = $this->getAccessToken();

        // First, get hotel list by city
        $hotelListResponse = Http::withToken($token)
            ->get($this->baseUrl . '/v1/reference-data/locations/hotels/by-city', [
                'cityCode' => $params['cityCode']
            ]);

        if (!$hotelListResponse->successful()) {
            throw new \Exception('Hotel list search failed');
        }

        $hotels = $hotelListResponse->json()['data'] ?? [];

        if (empty($hotels)) {
            return [];
        }

        // Get hotel IDs (limit to first 20 for performance)
        $hotelIds = array_slice(array_column($hotels, 'hotelId'), 0, 20);

        // Search for hotel offers
        $offersResponse = Http::withToken($token)
            ->get($this->baseUrl . '/v3/shopping/hotel-offers', [
                'hotelIds' => implode(',', $hotelIds),
                'checkInDate' => $params['checkInDate'],
                'checkOutDate' => $params['checkOutDate'],
                'adults' => $params['adults'],
                'roomQuantity' => $params['rooms'],
                'currency' => $params['currency'] ?? 'VND'
            ]);

        if ($offersResponse->successful()) {
            $data = $offersResponse->json();
            return $data['data'] ?? [];
        }

        throw new \Exception('Hotel offers search failed: ' . $offersResponse->body());
    }

    /**
     * Get hotel details by ID
     */
    public function getHotelDetails(string $hotelId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl . "/v1/reference-data/locations/hotels/{$hotelId}");

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        throw new \Exception('Hotel details fetch failed');
    }
}
