<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force IPv4 resolution to prevent slow/hanging IPv6 DNS lookups (cURL error 28 on Windows/PHP)
        \Illuminate\Support\Facades\Http::globalOptions([
            'force_ip_resolve' => 'v4',
        ]);
    }
}
