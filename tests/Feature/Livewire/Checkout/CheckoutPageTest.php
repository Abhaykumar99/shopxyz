<?php

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Address;
use App\Models\User;
use App\Support\Cart\Bag;
use App\Support\Demo\DemoOrders;
use Livewire\Livewire;

beforeEach(function () {
    seedCatalog();
});

it('sends guests to sign in and back to checkout afterwards', function () {
    // The developer shortcut signs in whichever customer the seeders made.
    User::factory()->googleCustomer()->create();
    fillCart(['MG-KK-1' => 1]);

    $this->get('/checkout')->assertRedirect(route('auth.login'));
    $this->get('/login')->assertSee('Sign in to place your order');
    $this->get('/dev/ui/as/customer')->assertRedirect(route('checkout.show'));
});

it('sends an empty bag back to the bag page', function () {
    signInCustomer();

    Livewire::test(CheckoutPage::class)->assertRedirect(route('cart.show'));
});

it('preselects the default address and cash on delivery', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->assertSet('addressId', $customer->addresses()->sole()->id)
        ->assertSet('paymentMethod', 'cod')
        ->assertSet('editingPhone', false)
        ->assertSee('+91 98300 12345');
});

it('asks for a mobile number before placing the order', function () {
    signInCustomerWithAddress(phone: null);
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->assertSet('editingPhone', true)
        ->call('placeOrder')
        ->assertHasErrors(['phoneForm.phone'])
        ->assertNoRedirect();

    expect(app(Bag::class)->isEmpty())->toBeFalse();
});

it('validates and saves the mobile number', function (string $input, ?string $error) {
    signInCustomer(phone: null);
    fillCart(['MG-KK-2' => 1]);

    $test = Livewire::test(CheckoutPage::class)
        ->set('phoneForm.phone', $input)
        ->call('savePhone');

    if ($error) {
        $test->assertHasErrors(['phoneForm.phone'])->assertSee($error);
    } else {
        $test->assertHasNoErrors()->assertSet('editingPhone', false);
        expect(auth()->user()->fresh()->phone)->toBe('9123456789');
    }
})->with([
    'valid with country code' => ['+91 91234 56789', null],
    'empty' => ['', 'Enter your mobile number so the delivery partner can reach you.'],
    'not a mobile number' => ['12345', 'Enter a 10-digit mobile number starting with 6, 7, 8 or 9.'],
]);

it('requires a delivery address', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->set('addressId', null)
        ->call('placeOrder')
        ->assertHasErrors(['addressId' => 'required'])
        ->assertSee('Choose where we should deliver.');
});

it('refuses an address outside the delivery area', function () {
    $customer = signInCustomerWithAddress();
    $outside = Address::factory()->for($customer)->create([
        'label' => 'Other', 'line1' => '12 MG Road',
        'city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110001',
    ]);
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->set('addressId', $outside->id)
        ->call('placeOrder')
        ->assertHasErrors(['addressId']);
});

it('rejects an unknown payment method', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->set('paymentMethod', 'card')
        ->call('placeOrder')
        ->assertHasErrors(['paymentMethod']);
});

it('places a cash on delivery order and shows the confirmation', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->set('note', '  Call before arriving  ')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect(route('orders.placed', 'ORD-10301'));

    $order = app(DemoOrders::class)->find('ORD-10301');
    expect($order->total())->toBe(52000)
        ->and($order->note)->toBe('Call before arriving')
        ->and($order->address->id)->toBe((string) $customer->addresses()->sole()->id)
        ->and(app(Bag::class)->isEmpty())->toBeTrue();

    $this->get(route('orders.placed', 'ORD-10301'))
        ->assertSee('Thank you, your order is placed')
        ->assertSee('pay ₹520 in cash');
});

it('sends UPI orders to the payment page', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->set('paymentMethod', 'upi')
        ->call('placeOrder')
        ->assertRedirect(route('orders.pay', 'ORD-10301'));
});

it('sends the customer back to the bag when the bag changed', function () {
    $customer = signInCustomerWithAddress();
    variantFor('GL-VITC-1')->forceFill(['stock_quantity' => 9])->save();
    fillCart(['GL-VITC-1' => 9]);
    $test = Livewire::test(CheckoutPage::class);

    // The shelf runs down while the customer is on the checkout page.
    variantFor('GL-VITC-1')->forceFill(['stock_quantity' => 1])->save();
    app()->forgetScopedInstances();

    $test->call('placeOrder')->assertRedirect(route('cart.show'));

    expect(app(DemoOrders::class)->find('ORD-10301'))->toBeNull();
});

it('adds a new address during checkout and selects it', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->call('newAddress')
        ->assertDispatched('open-modal', 'checkout-address')
        ->assertSet('addressForm.name', 'Priya Sharma')
        ->set('addressForm.line1', 'House 7, Gandhi Maidan Road')
        ->set('addressForm.pincode', '800004')
        ->set('addressForm.label', 'Other')
        ->call('saveAddress')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', 'checkout-address')
        ->assertSee('House 7, Gandhi Maidan Road');
});

it('checks the pincode as soon as it is entered', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-2' => 1]);

    Livewire::test(CheckoutPage::class)
        ->call('newAddress')
        ->set('addressForm.pincode', '560001')
        ->assertHasErrors(['addressForm.pincode'])
        ->assertSee('We don&#039;t deliver to 560001 yet.', false);
});

it('places a bulk order at slab prices through the ordinary checkout', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-3' => 20]);

    Livewire::test(CheckoutPage::class)
        ->assertSee('Wholesale price')
        ->assertSee('₹17,600')
        ->call('placeOrder')
        ->assertRedirect(route('orders.placed', 'ORD-10301'));

    $order = app(DemoOrders::class)->find('ORD-10301');
    expect($order->items[0])->toMatchArray(['sku' => 'MG-KK-3', 'quantity' => 20, 'paise' => 88000, 'wholesale' => true])
        ->and($order->total())->toBe(20 * 88000)
        ->and($order->hasWholesaleItems())->toBeTrue();
});

it('offers only UPI above the cash on delivery ceiling', function () {
    $customer = signInCustomerWithAddress();
    fillCart(['MG-KK-3' => 25]);

    Livewire::test(CheckoutPage::class)
        ->assertSet('paymentMethod', 'upi')
        ->assertSee('Cash on delivery is not available for this order')
        ->assertSee('Not available above ₹20,000')
        ->set('paymentMethod', 'cod')
        ->call('placeOrder')
        ->assertHasErrors(['paymentMethod'])
        ->assertSet('paymentMethod', 'upi');

    expect(app(Bag::class)->isEmpty())->toBeFalse();
});
