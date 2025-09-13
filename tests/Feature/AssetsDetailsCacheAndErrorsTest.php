<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssetsDetailsCacheAndErrorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_details_is_cached_for_60_seconds(): void
    {
        Cache::clear();
        $calls = 0;
        Http::fake([
            'api.coingecko.com/*' => function () use (&$calls) {
                $calls++;
                return Http::response([
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'symbol' => 'btc',
                    'image' => [
                        'large' => 'https://assets/bitcoin-large.png',
                    ],
                    'market_data' => [
                        'current_price' => ['usd' => 50000],
                        'price_change_percentage_24h' => 1.23,
                    ],
                ], 200);
            },
        ]);

        $this->getJson('/api/assets/bitcoin')->assertOk();
        $this->getJson('/api/assets/bitcoin')->assertOk();

        $this->assertSame(1, $calls, 'Asset details should be cached for 60 seconds');
    }

    public function test_asset_details_returns_404_when_not_found(): void
    {
        Cache::clear();
        Http::fake([
            'api.coingecko.com/*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->getJson('/api/assets/does-not-exist')
            ->assertStatus(404)
            ->assertJson(['message' => 'Asset not found']);
    }

    public function test_asset_history_returns_404_when_not_found(): void
    {
        Cache::clear();
        Http::fake([
            'api.coingecko.com/*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->getJson('/api/assets/does-not-exist/history?days=7')
            ->assertStatus(404)
            ->assertJson(['message' => 'Asset history not found']);
    }
}
