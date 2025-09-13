<?php

namespace Tests\Feature;

use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssetsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_list_returns_ok_and_expected_shape(): void
    {
        Cache::clear();
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'symbol' => 'btc',
                    'image' => 'https://assets/bitcoin.png',
                    'current_price' => 50000,
                    'price_change_percentage_24h' => 1.23,
                ],
            ], 200),
        ]);

        $this->getJson('/api/assets')
            ->assertOk()
            ->assertJson([[
                'id' => 'bitcoin',
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'image' => 'https://assets/bitcoin.png',
                'current_price' => 50000,
                'price_change_percentage_24h' => 1.23,
                'is_favorite' => false,
            ]]);
    }

    public function test_assets_list_marks_favorites_true_when_exists(): void
    {
        Cache::clear();
        Favorite::create(['asset_id' => 'bitcoin']);

        Http::fake([
            'api.coingecko.com/*' => Http::response([
                [
                    'id' => 'bitcoin',
                    'name' => 'Bitcoin',
                    'symbol' => 'btc',
                    'image' => 'https://assets/bitcoin.png',
                    'current_price' => 50000,
                    'price_change_percentage_24h' => 1.23,
                ],
            ], 200),
        ]);

        $this->getJson('/api/assets')
            ->assertOk()
            ->assertJson([[
                'id' => 'bitcoin',
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'image' => 'https://assets/bitcoin.png',
                'current_price' => 50000,
                'price_change_percentage_24h' => 1.23,
                'is_favorite' => true,
            ]]);
    }

    public function test_asset_details_returns_ok_and_expected_shape(): void
    {
        Cache::clear();
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'id' => 'bitcoin',
                'name' => 'Bitcoin',
                'symbol' => 'btc',
                'image' => [
                    'large' => 'https://assets/bitcoin-large.png',
                    'small' => 'https://assets/bitcoin-small.png',
                ],
                'market_data' => [
                    'current_price' => ['usd' => 50000],
                    'price_change_percentage_24h' => 1.23,
                ],
            ], 200),
        ]);

        $this->getJson('/api/assets/bitcoin')
            ->assertOk()
            ->assertJson([
                'id' => 'bitcoin',
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'image' => 'https://assets/bitcoin-large.png',
                'market_data' => [
                    'current_price' => ['usd' => 50000],
                    'price_change_percentage_24h' => 1.23,
                ],
            ]);
    }

    public function test_assets_list_is_cached_for_60_seconds(): void
    {
        Cache::clear();
        $callCount = 0;
        Http::fake([
            'api.coingecko.com/*' => function () use (&$callCount) {
                $callCount++;
                return Http::response([
                    [
                        'id' => 'bitcoin',
                        'name' => 'Bitcoin',
                        'symbol' => 'btc',
                        'image' => 'https://assets/bitcoin.png',
                        'current_price' => 50000,
                        'price_change_percentage_24h' => 1.23,
                    ],
                ], 200);
            },
        ]);

        // First call should hit HTTP
        $this->getJson('/api/assets')->assertOk();
        // Second call should be cached
        $this->getJson('/api/assets')->assertOk();

        $this->assertSame(1, $callCount, 'Expected only one HTTP call due to caching');
    }
}
