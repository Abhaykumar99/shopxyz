<?php

use App\Actions\Cart\MergeGuestCart;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Support\Cart\Bag;
use App\Support\Catalog\WholesaleCatalog;
use App\Support\ShopSettings;

beforeEach(function () {
    seedCatalog();
    config([
        'shop.delivery.charge_paise' => 4000,
        'shop.delivery.free_above_paise' => 49900,
        'shop.delivery.min_order_paise' => 9900,
        'shop.payment.cod_max_paise' => 2000000,
    ]);
    app()->forgetScopedInstances();
});

it('adds units up to the per-line limit', function () {
    $bag = app(Bag::class);

    $added = $bag->add(variantFor('RS-MASC-1'), 15);

    expect($added)->toBe(CartItem::MAX_PER_LINE)
        ->and($bag->quantityOf('RS-MASC-1'))->toBe(CartItem::MAX_PER_LINE);
});

it('never adds more than the stock on the shelf', function () {
    $bag = app(Bag::class);
    $variant = variantFor('BB-LIP-5');

    $first = $bag->add($variant, 1);
    $second = $bag->add($variant, 5);

    expect([$first, $second])->toBe([1, 1])
        ->and($bag->quantityOf('BB-LIP-5'))->toBe(2);
});

it('refuses an item that is out of stock', function () {
    $bag = app(Bag::class);

    expect($bag->add(variantFor('BB-LIP-3')))->toBe(0)
        ->and($bag->isEmpty())->toBeTrue();
});

it('removes a line when its quantity is set to zero', function () {
    $bag = fillCart(['MG-KK-1' => 2]);

    $stored = $bag->setQuantity(variantFor('MG-KK-1'), 0);

    expect($stored)->toBe(0)
        ->and($bag->isEmpty())->toBeTrue();
});

it('never adds a line through a quantity change', function () {
    $bag = app(Bag::class);

    expect($bag->setQuantity(variantFor('MG-KK-1'), 3))->toBe(0)
        ->and($bag->isEmpty())->toBeTrue();
});

it('charges delivery below the free-delivery threshold', function () {
    $bag = fillCart(['MG-KK-1' => 1]);

    expect($bag->summary(app(ShopSettings::class)))->toMatchArray([
        'subtotal' => 28000,
        'delivery' => 4000,
        'total' => 32000,
        'free_delivery_shortfall' => 21900,
        'meets_minimum' => true,
        'items' => 1,
    ]);
});

it('delivers free at the threshold and totals discounts against MRP', function () {
    $bag = fillCart(['BB-LIP-1' => 1, 'MG-KK-1' => 1]);

    expect($bag->summary(app(ShopSettings::class)))->toMatchArray([
        'mrp' => 77900,
        'subtotal' => 62900,
        'discount' => 15000,
        'delivery' => 0,
        'total' => 62900,
        'free_delivery_shortfall' => 0,
    ]);
});

it('flags a bag below the minimum order', function () {
    config(['shop.delivery.min_order_paise' => 50000]);
    app()->forgetScopedInstances();
    $bag = fillCart(['BB-KAJ-1' => 1]);

    expect($bag->summary(app(ShopSettings::class))['meets_minimum'])->toBeFalse();
});

it('reports a line the shelf can no longer fill', function () {
    $bag = fillCart(['GL-VITC-1' => 2]);

    // The shelf runs down after the bag was filled.
    variantFor('GL-VITC-1')->forceFill(['stock_quantity' => 1])->save();
    app()->forgetScopedInstances();

    expect(app(Bag::class)->hasUnavailableLines())->toBeTrue();
});

it('lets a wholesale product be ordered in bulk, past the retail line limit', function () {
    $bag = app(Bag::class);

    $added = $bag->add(variantFor('MG-KK-3'), 40);

    expect($added)->toBe(40)
        ->and($bag->lines()->first()->unitPrice())->toBe(88000)
        ->and($bag->lines()->first()->isWholesale())->toBeTrue();
});

it('caps a wholesale line at the largest order we handle', function () {
    $bag = fillCart(['MG-KK-3' => 20]);

    expect($bag->setQuantity(variantFor('MG-KK-3'), WholesaleCatalog::MAX_QUANTITY + 1))
        ->toBe(WholesaleCatalog::MAX_QUANTITY);
});

it('prices a small quantity of a wholesale product at retail', function () {
    $bag = fillCart(['MG-KK-3' => 2]);

    expect($bag->lines()->first())
        ->isWholesale()->toBeFalse()
        ->unitPrice()->toBe(99000);
});

it('orders bulk quantities in rather than blocking them on shelf stock', function () {
    $bag = fillCart(['MG-KK-3' => 500]);

    expect($bag->hasUnavailableLines())->toBeFalse()
        ->and($bag->lines()->first()->isMadeToOrder())->toBeTrue();
});

it('totals a bag of wholesale and retail lines and reports the wholesale saving', function () {
    $bag = fillCart(['MG-KK-3' => 20, 'BB-LIP-1' => 1]);

    expect($bag->summary(app(ShopSettings::class)))->toMatchArray([
        'subtotal' => 20 * 88000 + 34900,
        'wholesale_lines' => 1,
        'wholesale_saving' => 20 * (99000 - 88000),
        'cod_available' => true,
        'items' => 21,
    ]);
});

it('withdraws cash on delivery above the ceiling', function () {
    $bag = fillCart(['MG-KK-3' => 25]);

    expect($bag->summary(app(ShopSettings::class)))
        ->subtotal->toBe(25 * 88000)
        ->cod_available->toBeFalse();
});

it('offers cash on delivery up to the ceiling', function () {
    expect(fillCart(['MG-KK-3' => 5])->summary(app(ShopSettings::class))['cod_available'])->toBeTrue();
});

it('leaves no row behind for a visitor who only looks around', function () {
    app(Bag::class)->lines();

    expect(Cart::count())->toBe(0);
});

it('keeps a guest bag against the session and a customer bag against the account', function () {
    fillCart(['MG-KK-1' => 1]);

    expect(Cart::sole())
        ->user_id->toBeNull()
        ->session_id->toBe(session()->getId());

    $customer = signInCustomer();
    app()->forgetScopedInstances();

    fillCart(['BB-LIP-1' => 1]);

    expect(Cart::where('user_id', $customer->id)->exists())->toBeTrue();
});

it('reads the same bag for a customer across sessions', function () {
    $customer = signInCustomer();
    fillCart(['MG-KK-1' => 2]);

    session()->regenerate();
    app()->forgetScopedInstances();

    expect(app(Bag::class)->quantityOf('MG-KK-1'))->toBe(2);
});

it('empties the bag without losing it', function () {
    $bag = fillCart(['MG-KK-1' => 1]);

    $bag->clear();

    expect($bag->isEmpty())->toBeTrue()
        ->and(Cart::count())->toBe(1);
});

it('carries a guest bag onto the account at sign-in', function () {
    fillCart(['MG-KK-1' => 2]);
    $guestSession = session()->getId();

    $customer = User::factory()->googleCustomer()->create();
    app(MergeGuestCart::class)->handle($customer, $guestSession);

    $this->actingAs($customer);
    app()->forgetScopedInstances();

    expect(app(Bag::class)->quantityOf('MG-KK-1'))->toBe(2)
        ->and(Cart::whereNull('user_id')->count())->toBe(0);
});

it('keeps the larger quantity when both bags hold the same product', function () {
    $customer = User::factory()->googleCustomer()->create();
    $mine = Cart::create(['user_id' => $customer->id]);
    $mine->items()->create(['product_variant_id' => variantFor('MG-KK-1')->id, 'quantity' => 1]);

    fillCart(['MG-KK-1' => 3]);
    $guestSession = session()->getId();

    app(MergeGuestCart::class)->handle($customer, $guestSession);

    $this->actingAs($customer);
    app()->forgetScopedInstances();

    expect(app(Bag::class)->quantityOf('MG-KK-1'))->toBe(3);
});

it('does nothing when there was no guest bag', function () {
    $customer = User::factory()->googleCustomer()->create();

    app(MergeGuestCart::class)->handle($customer, session()->getId());

    expect(Cart::count())->toBe(0);
});

/*
 * The wiring from the sign-in controllers into MergeGuestCart is not covered
 * here on purpose. The suite runs on the array session driver (phpunit.xml), so
 * session state does not survive from one test request to the next and a guest
 * bag can never be found by a later request, however correct the code is. The
 * three tests above cover the merging itself; the wiring is checked in a real
 * browser instead.
 */
