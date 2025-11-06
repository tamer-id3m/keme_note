<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateInternal
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('X-Internal-Token');

        if ($header !== config('services.internal.token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}