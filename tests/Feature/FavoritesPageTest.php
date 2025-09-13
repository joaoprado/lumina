<?php

namespace Tests\Feature;

use Tests\TestCase;

class FavoritesPageTest extends TestCase
{
    public function test_favorites_page_renders_ok(): void
    {
        $this->get('/favorites')->assertStatus(200);
    }
}
