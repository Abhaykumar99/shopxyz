<?php

use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\InfoPageController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Account\AddressBook;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Livewire\Account\Profile;
use App\Livewire\Cart\CartPage;
use App\Livewire\Checkout\CheckoutPage;
use App\Livewire\Checkout\OrderPlaced;
use App\Livewire\Checkout\UpiPayment;
use App\Livewire\Delivery\CashHistory;
use App\Livewire\Delivery\CashSummary;
use App\Livewire\Delivery\DeliveryHistory;
use App\Livewire\Delivery\DeliveryList;
use App\Livewire\Delivery\DeliveryShow;
use App\Livewire\Delivery\Profile as DeliveryProfile;
use App\Livewire\Delivery\SignIn as DeliverySignIn;
use App\Livewire\Shop\CategoryIndex;
use App\Livewire\Shop\CategoryShow;
use App\Livewire\Shop\Home;
use App\Livewire\Shop\ProductShow;
use App\Livewire\Shop\Search;
use App\Livewire\Wholesale\QuotePage;
use App\Livewire\Wholesale\WholesalePage;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer website
|--------------------------------------------------------------------------
*/

Route::livewire('/', Home::class)->name('shop.home');
Route::livewire('/categories', CategoryIndex::class)->name('shop.categories');
Route::livewire('/c/{category}', CategoryShow::class)->name('shop.category');
Route::livewire('/search', Search::class)->name('shop.search');
Route::livewire('/p/{product}', ProductShow::class)->name('shop.product');
Route::livewire('/cart', CartPage::class)->name('cart.show');
Route::livewire('/wholesale', WholesalePage::class)->name('wholesale.index');
Route::livewire('/wholesale/quote', QuotePage::class)->name('wholesale.quote');

Route::get('/login', [SessionController::class, 'create'])->name('auth.login');
Route::post('/logout', [SessionController::class, 'destroy'])->name('auth.logout');

// Google sign-in (ADR-004). Throttled because the callback is the one auth
// endpoint anyone on the internet can reach without an account.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('/checkout', CheckoutPage::class)->name('checkout.show');
    Route::livewire('/orders/{order}/pay', UpiPayment::class)->name('orders.pay');
    Route::livewire('/orders/{order}/placed', OrderPlaced::class)->name('orders.placed');

    Route::livewire('/account', Profile::class)->name('account.profile');
    Route::livewire('/account/orders', OrderList::class)->name('account.orders');
    Route::livewire('/account/orders/{order}', OrderShow::class)->name('account.order');
    Route::livewire('/account/addresses', AddressBook::class)->name('account.addresses');
});

/*
|--------------------------------------------------------------------------
| Delivery panel (mobile web)
|--------------------------------------------------------------------------
*/

Route::prefix('delivery')->name('delivery.')->group(function () {
    Route::livewire('/login', DeliverySignIn::class)->name('login');

    Route::middleware(['auth', 'active', 'delivery'])->group(function () {
        Route::livewire('/', DeliveryList::class)->name('index');
        Route::livewire('/cash', CashSummary::class)->name('cash');
        Route::livewire('/cash/history', CashHistory::class)->name('cash.history');
        Route::livewire('/history', DeliveryHistory::class)->name('history');
        Route::livewire('/profile', DeliveryProfile::class)->name('profile');
        Route::livewire('/{order}', DeliveryShow::class)->name('order');
    });
});

/*
|--------------------------------------------------------------------------
| Admin printing (labels and invoices, ADR-014)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin/print')->name('admin.print.')->group(function () {
    Route::get('/orders/{order}/label', [PrintController::class, 'label'])->name('label');
    Route::get('/orders/{order}/invoice', [PrintController::class, 'invoice'])->name('invoice');
});

// Payment screenshots live on the private disk and are streamed, never served directly.
Route::middleware(['auth', 'admin'])
    ->get('/admin/payments/{payment}/proof', [PrintController::class, 'proof'])
    ->name('admin.payment-proof');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Where browsers post Content-Security-Policy violations while the policy is
// report-only. Unauthenticated by nature, so it is throttled like the OAuth
// callback and answers with nothing.
Route::post('/csp-report', CspReportController::class)
    ->middleware('throttle:30,1')
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('csp.report');

Route::get('/pages/{page}', InfoPageController::class)
    ->whereIn('page', array_keys(InfoPageController::PAGES))
    ->name('pages.show');

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/dev.php';
}

Route::get('/bypass-login', function () {
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'tester@example.com'],
        [
            'name' => 'Testing User',
            'role' => 'customer',
            'phone' => '9999999999',
            'is_active' => true,
        ]
    );
    auth()->login($user);
    return redirect()->intended('/')->with('toast', ['message' => 'Logged in via bypass!', 'tone' => 'success']);
});

Route::get('/bypass-delivery', function () {
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'delivery@example.com'],
        [
            'name' => 'Delivery Boy',
            'role' => 'delivery',
            'phone' => '8888888888',
            'is_active' => true,
            'password' => bcrypt('password'),
        ]
    );
    auth()->login($user);
    return redirect('/delivery')->with('toast', ['message' => 'Logged in as delivery via bypass!', 'tone' => 'success']);
});

Route::get('/bypass-admin', function () {
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Admin User',
            'role' => 'admin',
            'phone' => '7777777777',
            'is_active' => true,
            'password' => bcrypt('password'),
        ]
    );
    auth()->login($user);
    return redirect('/admin');
});

Route::get('/debug-clear-cache', function () {
    return response()->json([
        'status' => 'Cache cleared on Render!',
        'default_connection' => config('database.default'),
        'database_url' => env('DATABASE_URL'),
        'db_host' => env('DB_HOST'),
        'categories' => \App\Models\Category::count(),
        'products' => \App\Models\Product::count(),
        'home_sections' => \App\Models\HomeSection::count(),
        'session_driver' => config('session.driver'),
        'db_persistent' => config('database.connections.mysql.options.' . PDO::ATTR_PERSISTENT),
        'live_sections' => \App\Models\HomeSection::live()->count(),
        'live_banners' => \App\Models\Banner::live()->count(),
        'mobile_hero' => \App\Models\Banner::live()->placement(\App\Enums\BannerPlacement::MobileHero)->count(),
        'desktop_hero' => \App\Models\Banner::live()->placement(\App\Enums\BannerPlacement::DesktopHero)->count(),
        'log' => file_exists(storage_path('logs/laravel.log')) ? substr(file_get_contents(storage_path('logs/laravel.log')), -5000) : 'No log file',
    ]);
});
