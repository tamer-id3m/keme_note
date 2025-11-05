<?php

namespace App\Providers;

use App\Services\Internal\ClinicSettingService;
use App\Services\Internal\Contracts\ClinicSettingServiceInterface;
use Illuminate\Support\ServiceProvider;

class InternalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // You could add internal-specific logic here later,
        // like route macros, observers, etc.
    }
}