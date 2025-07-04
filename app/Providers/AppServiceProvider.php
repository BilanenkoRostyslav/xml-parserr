<?php

namespace App\Providers;

use App\Repositories\Abstracts\MainRepositoryInterface;
use App\Repositories\MainRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
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
        DB::prohibitDestructiveCommands($this->app->isProduction());
        Url::forceScheme('https');

        $this->app->bind(MainRepositoryInterface::class, MainRepository::class);
    }
}
