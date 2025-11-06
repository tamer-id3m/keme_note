<?php

namespace App\Http\Middleware;

use App\Http\Clients\UserClient;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    protected $userClient;
    use ApiResponseTrait;
    public function __construct(UserClient $userClient)
    {
        $this->userClient = $userClient;
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$this->userClient->hasPermission($permission)) {
            return $this->ApiResponse('You dont have permission', 403);
        }
        return $next($request);
    }
}