<?php

namespace Ddys\Laravel\Http\Controllers;

use Ddys\Laravel\Exceptions\DdysException;
use Ddys\Laravel\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController
{
    public function __construct(protected RequestService $requests) {}

    public function submit(Request $request): JsonResponse
    {
        try {
            $payload = $this->requests->submit($request->all(), $request->ip() ?: 'anonymous');

            return response()->json(['success' => true, 'data' => $payload]);
        } catch (DdysException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => $e->status(),
            ], $e->status() >= 400 ? $e->status() : 500);
        }
    }
}

