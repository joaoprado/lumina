<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\RequestException;

class CoinGeckoClient
{
    private const string BASE_URL = 'https://api.coingecko.com/api/v3';

    public function listMarkets(): array
    {
        $cacheKey = 'coingecko.markets.top10';
        return Cache::remember($cacheKey, 60, function () {
            $response = Http::acceptJson()
                ->retry(1, 200)
                ->get(self::BASE_URL.'/coins/markets', [
                    'vs_currency' => 'usd',
                    'order' => 'market_cap_desc',
                    'per_page' => 10,
                    'page' => 1,
                    'sparkline' => 'false',
                ])->throw();

            $data = $response->json();
            if (!is_array($data)) {
                return [];
            }
            return array_map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? null,
                    'symbol' => Str::upper($item['symbol'] ?? ''),
                    'image' => $item['image'] ?? null,
                    'current_price' => $item['current_price'] ?? null,
                    'price_change_percentage_24h' => $item['price_change_percentage_24h'] ?? null,
                ];
            }, $data);
        });
    }

    public function getAsset(string $id): array
    {
        $cacheKey = 'coingecko.asset.'.$id;
        return Cache::remember($cacheKey, 60, function () use ($id) {
            $response = Http::acceptJson()
                ->retry(1, 200)
                ->get(self::BASE_URL.'/coins/'.urlencode($id))
                ->throw();

            $data = $response->json();
            if (!is_array($data)) {
                return [];
            }
            return [
                'id' => $data['id'] ?? null,
                'name' => $data['name'] ?? null,
                'symbol' => Str::upper($data['symbol'] ?? ''),
                'image' => $data['image']['large'] ?? ($data['image']['small'] ?? null),
                'market_data' => [
                    'current_price' => [
                        'usd' => $data['market_data']['current_price']['usd'] ?? null,
                    ],
                    'price_change_percentage_24h' => $data['market_data']['price_change_percentage_24h'] ?? null,
                ],
            ];
        });
    }
}
