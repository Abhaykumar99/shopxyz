<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\InfoPageController;
use App\Http\Middleware\RequireDemoCustomer;
use App\Livewire\Account\AddressBook;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Livewire\Account\Profile;
use App\Livewire\Cart\CartPage;
use App\Livewire\Checkout\CheckoutPage;
use App\Livewire\Checkout\OrderPlaced;
use App\Livewire\Checkout\UpiPayment;
use App\Livewire\Shop\CategoryIndex;
use App\Livewire\Shop\CategoryShow;
use App\Livewire\Shop\Home;
use App\Livewire\Shop\ProductShow;
use App\Livewire\Shop\Search;
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

Route::get('/pages/{page}', InfoPageController::class)
    ->whereIn('page', array_keys(InfoPageController::PAGES))
    ->name('pages.show');

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/dev.php';
}
