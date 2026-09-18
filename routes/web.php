<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\InfoPageController;
use App\Http\Middleware\RequireDemoCustomer;
use App\Http\Middleware\RequireDemoDeliveryBoy;
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

// Phase 2: demo sign-in guard. Phase 4: replaced by `auth` (Google sign-in).
Route::middleware(RequireDemoCustomer::class)->group(function () {
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

    // Phase 3: demo sign-in guard. Phase 4: replaced by `auth` plus the delivery role.
    Route::middleware(RequireDemoDeliveryBoy::class)->group(function () {
        Route::livewire('/', DeliveryList::class)->name('index');
        Route::livewire('/cash', CashSummary::class)->name('cash');
        Route::livewire('/cash/history', CashHistory::class)->name('cash.history');
        Route::livewire('/history', DeliveryHistory::class)->name('history');
        Route::livewire('/profile', DeliveryProfile::class)->name('profile');
        Route::livewire('/{order}', DeliveryShow::class)->name('order');
    });
});

Route::get('/pages/{page}', InfoPageController::class)
    ->whereIn('page', array_keys(InfoPageController::PAGES))
    ->name('pages.show');

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/dev.php';
}
