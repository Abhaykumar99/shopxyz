<?php

namespace App\Providers;

use App\Support\ShopSettings;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(
            ShopSettings::class,
            fn ($app): ShopSettings => ShopSettings::fromConfig($app->make(Repository::class)),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ViewFacade::composer('*', function (View $view): void {
            $view->with('shop', $this->app->make(ShopSettings::class));
        });
    }
}
