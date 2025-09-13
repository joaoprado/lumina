<?php

namespace Tests\Feature;

use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_favorite_and_is_idempotent(): void
    {
        $this->postJson('/api/favorites', ['assetId' => 'bitcoin'])
            ->assertCreated()
            ->assertJsonFragment(['asset_id' => 'bitcoin']);

        $this->postJson('/api/favorites', ['assetId' => 'bitcoin'])
            ->assertCreated()
            ->assertJsonFragment(['asset_id' => 'bitcoin']);

        $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseHas('favorites', ['asset_id' => 'bitcoin']);
    }

    public function test_list_returns_favorites(): void
    {
        Favorite::create(['asset_id' => 'bitcoin']);
        Favorite::create(['asset_id' => 'ethereum']);

        $this->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonFragment(['asset_id' => 'bitcoin'])
            ->assertJsonFragment(['asset_id' => 'ethereum']);
    }

    public function test_delete_removes_favorite_and_is_idempotent(): void
    {
        Favorite::create(['asset_id' => 'bitcoin']);

        $this->deleteJson('/api/favorites/bitcoin')
            ->assertNoContent();

        $this->deleteJson('/api/favorites/bitcoin')
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', ['asset_id' => 'bitcoin']);
    }
}
