<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonRequest
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json([
                'message' => 'This endpoint accepts JSON only.',
            ], 415);
        }

        return $next($request);
    }
}
