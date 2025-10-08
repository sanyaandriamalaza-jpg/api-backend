<?php

namespace App\Providers;

use App\Services\MultiUserProvider;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class MultiUserAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Le driver doit s'appeler "multi_user" (comme dans auth.php)
        Auth::provider('multi_user', function ($app, array $config) {
            return new MultiUserProvider($app->make(UserService::class));
        });
    }
}
