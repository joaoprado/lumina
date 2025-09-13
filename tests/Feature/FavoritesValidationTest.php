<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_favorites_requires_asset_id(): void
    {
        $this->postJson('/api/favorites', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assetId']);
    }
}
