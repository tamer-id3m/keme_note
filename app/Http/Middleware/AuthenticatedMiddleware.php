<?php

namespace App\Http\Middleware;

use App\Http\Clients\UserClient;
use App\Models\GenericUser;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedMiddleware
{
    use ApiResponseTrait;
    protected $userClient;
    public function __construct(UserClient $userClient)
    {
        $this->userClient = $userClient;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->userClient->authUser();
        if (!$user) {
            return $this->ApiResponse('Unauthenticated', 401);
        }
        $user->pcmDoctors = collect($user->pcmDoctors);
        $user->staffDoctors = collect($user->staffDoctors);
        $user->minors = collect($user->minors);
        $genericUser = new GenericUser((array) $user);
        auth()->guard()->setUser($genericUser);
        $request->setUserResolver(function () use ($genericUser) {
            return $genericUser;
        });
        return $next($request);
    }
}