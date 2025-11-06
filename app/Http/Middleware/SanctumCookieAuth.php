<?php

namespace App\Http\Middleware;

use App\Http\Clients\UserClient;
use App\Support\SanctumUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanctumCookieAuth
{
    public function __construct(protected UserClient $userClient) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authData = $this->userClient->authUser();
        if (!$this->userClient->authenticated() || !$authData) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Fetch role/permission info from your Auth microservice
        $roles = $this->userClient->roles();
        $permissions = []; // You can also load them via $this->userClient->hasPermission()

        // Hydrate a lightweight user object into the current auth context
        $user = new SanctumUser(
            id: $authData->id,
            roles: $roles,
            permissions: $permissions,
            attributes: (array) $authData
        );

        auth()->setUser($user);

        return $next($request);
    }
}