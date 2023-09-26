<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Document::class => DocumentPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
