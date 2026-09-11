<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthenticated();
        }

        $client = ApiClient::query()
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return $this->unauthenticated();
        }

        $request->setUserResolver(fn (): ApiClient => $client);

        $client->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
