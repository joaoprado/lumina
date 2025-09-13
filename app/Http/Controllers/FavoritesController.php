<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoritesController
{
    public function index(): JsonResponse
    {
        $favorites = Favorite::query()
            ->orderByDesc('created_at')
            ->get(['asset_id', 'created_at', 'updated_at']);

        return response()->json($favorites);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assetId' => ['required', 'string'],
        ]);

        $assetId = $validated['assetId'];

        $favorite = Favorite::firstOrCreate(['asset_id' => $assetId]);

        return response()->json($favorite->only(['asset_id', 'created_at', 'updated_at']), 201);
    }

    public function destroy(string $assetId): JsonResponse
    {
        Favorite::where('asset_id', $assetId)->delete();
        return response()->json(null, 204);
    }
}
