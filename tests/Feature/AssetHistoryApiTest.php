<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssetHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_history_returns_ok_and_expected_shape(): void
    {
        Cache::clear();
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'prices' => [
                    [1726200000000, 50000],
                    [1726286400000, 50500],
                ],
            ], 200),
        ]);

        $this->getJson('/api/assets/bitcoin/history?days=7')
            ->assertOk()
            ->assertJson([
                'id' => 'bitcoin',
                'days' => 7,
                'prices' => [
                    ['timestamp' => 1726200000000, 'price' => 50000],
                    ['timestamp' => 1726286400000, 'price' => 50500],
                ],
            ]);
    }

    public function test_asset_history_is_cached_for_60_seconds(): void
    {
        Cache::clear();
        $calls = 0;
        Http::fake([
            'api.coingecko.com/*' => function () use (&$calls) {
                $calls++;

                return Http::response([
                    'prices' => [
                        [1726200000000, 50000],
                    ],
                ], 200);
            },
        ]);

        $this->getJson('/api/assets/bitcoin/history?days=7')->assertOk();
        $this->getJson('/api/assets/bitcoin/history?days=7')->assertOk();

        $this->assertSame(1, $calls, 'History should be cached for 60 seconds');
    }
}
