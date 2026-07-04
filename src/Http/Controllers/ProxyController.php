<?php

namespace Ddys\Laravel\Http\Controllers;

use Ddys\Laravel\Client;
use Ddys\Laravel\Exceptions\DdysException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProxyController
{
    public function __construct(protected Client $client) {}

    public function show(Request $request, string $route): JsonResponse
    {
        try {
            return response()->json($this->client->proxy($route, $request->query()));
        } catch (DdysException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => $e->status(),
            ], $e->status() >= 400 ? $e->status() : 500);
        }
    }
}

