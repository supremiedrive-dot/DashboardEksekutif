<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_active) {
            $token = $request->user()?->currentAccessToken();
            if ($token instanceof PersonalAccessToken) $token->delete();
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return $next($request);
    }
}
