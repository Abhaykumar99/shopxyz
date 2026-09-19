<?php

namespace App\Providers;

use App\Support\Cart\Bag;
use App\Support\Home\HomeContent;
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

        // One per request, so the homepage reads its banners, blocks and rails
        // once however many times a render asks for them.
        $this->app->scoped(HomeContent::class);

        // The bag is read several times in one render: the header count, the
        // page, the summary.
        $this->app->scoped(Bag::class);
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
