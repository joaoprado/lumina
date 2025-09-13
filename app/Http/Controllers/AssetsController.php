<?php

namespace App\Http\Controllers;

use App\Services\CoinGeckoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssetsController
{
    public function __construct(private readonly CoinGeckoClient $client)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $assets = $this->client->listMarkets();
            return response()->json($assets);
        } catch (\Throwable $e) {
            Log::error('Assets index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch assets'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $asset = $this->client->getAsset($id);
            if (empty($asset) || empty($asset['id'])) {
                throw new NotFoundHttpException('Asset not found');
            }
            return response()->json($asset);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => 'Asset not found'], 404);
        } catch (\Throwable $e) {
            Log::error('Asset details failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch asset details'], 500);
        }
    }
}
