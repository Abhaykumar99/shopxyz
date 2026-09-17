<?php

use App\Livewire\Wholesale\WholesalePage;
use App\Support\Demo\DemoWholesale;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

function validEnquiry(): array
{
    return [
        'form.businessName' => 'Sharma Sweets and Gifts',
        'form.contactName' => 'Ravi Sharma',
        'form.phone' => '98300 11111',
        'form.businessType' => 'retail',
        'form.city' => 'Patna',
        'form.pincode' => '800001',
    ];
}

function fillEnquiry($test, array $overrides = [])
{
    foreach ([...validEnquiry(), ...$overrides] as $field => $value) {
        $test->set($field, $value);
    }

    return $test;
}

it('shows the wholesale page with price slabs and the navigation tab marked current', function () {
    $response = $this->get('/wholesale');

    expect($response->getContent())->toMatch('#<a href="'.preg_quote(route('wholesale.index'), '#').'"\s+aria-current="page"#');
    $response
        ->assertOk()
        ->assertSee('Bulk sweets, gifts and beauty for your business.')
        ->assertSee('Kaju katli with silver leaf')
        ->assertSee('5 to 19')
        ->assertSee('50 or more')
        ->assertSee('₹840 each')
        ->assertSee('Minimum 5');
});

it('links to wholesale from every shop page', function () {
    $this->get('/')->assertSee('href="'.route('wholesale.index').'"', false);
    $this->get('/cart')->assertSee('Wholesale and bulk orders');
});

it('filters wholesale products by category and search', function () {
    Livewire::test(WholesalePage::class)
        ->set('category', 'cosmetics')
        ->assertSee('Smudge-proof kajal')
        ->assertDontSee('Assorted mithai box')
        ->set('category', '')
        ->set('search', 'diya')
        ->assertSee('Brass diya set')
        ->assertDontSee('Smudge-proof kajal');
});

it('ignores an unknown category', function () {
    Livewire::withQueryParams(['category' => 'cars'])
        ->test(WholesalePage::class)
        ->assertSee('Assorted mithai box')
        ->assertSee('Smudge-proof kajal');
});

it('adds products to the enquiry at the right slab price', function () {
    Livewire::test(WholesalePage::class)
        ->set('quantities.MG-KK-3', 25)
        ->call('addToEnquiry', 'MG-KK-3')
        ->assertDispatched('toast', tone: 'success')
        ->assertSet('listQuantities', ['MG-KK-3' => 25])
        ->assertSee('₹880 per 1 kg box')
        ->assertSee('₹22,000');
});

it('raises quantities below the minimum order to the minimum', function () {
    Livewire::test(WholesalePage::class)
        ->set('quantities.MG-KK-3', 2)
        ->call('addToEnquiry', 'MG-KK-3')
        ->assertSet('listQuantities.MG-KK-3', 5);
});

it('keeps enquiry quantities at or above the minimum when edited', function () {
    Livewire::test(WholesalePage::class)
        ->call('addToEnquiry', 'MG-KK-3')
        ->set('listQuantities.MG-KK-3', 3)
        ->assertSet('listQuantities.MG-KK-3', 5)
        ->assertDispatched('toast', tone: 'warning')
        ->set('listQuantities.MG-KK-3', 60)
        ->assertSet('listQuantities.MG-KK-3', 60)
        ->assertSee('₹840 per 1 kg box');
});

it('removes a product from the enquiry', function () {
    Livewire::test(WholesalePage::class)
        ->call('addToEnquiry', 'MG-KK-3')
        ->call('removeFromEnquiry', 'MG-KK-3')
        ->assertSet('listQuantities', [])
        ->assertSee('Add products from the price list.');
});

it('ignores unknown products', function () {
    Livewire::test(WholesalePage::class)
        ->call('addToEnquiry', 'NOPE-1')
        ->assertSet('listQuantities', []);
});

it('sends an enquiry with products and shows the reference', function () {
    $test = Livewire::test(WholesalePage::class)->call('addToEnquiry', 'UG-HMP-1');

    fillEnquiry($test, ['form.gstin' => '10abcde1234f1z5', 'form.neededBy' => now()->addWeek()->toDateString()])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submittedReference', 'WQ-5101')
        ->assertSee('Thank you, your enquiry is with us')
        ->assertSee('WQ-5101')
        ->assertSee('+91 98300 11111');

    $enquiry = app(DemoWholesale::class)->enquiry('WQ-5101');
    expect($enquiry['details'])->toMatchArray(['gstin' => '10ABCDE1234F1Z5', 'phone' => '9830011111'])
        ->and($enquiry['items'][0])->toMatchArray(['sku' => 'UG-HMP-1', 'quantity' => 10, 'unit_paise' => 134900])
        ->and(app(DemoWholesale::class)->count())->toBe(0);
});

it('needs a message when no products are listed', function () {
    fillEnquiry(Livewire::test(WholesalePage::class))
        ->call('submit')
        ->assertHasErrors(['form.message' => 'required'])
        ->assertSee('Add products to your enquiry, or describe what you need here.');

    fillEnquiry(Livewire::test(WholesalePage::class), ['form.message' => '200 sweet boxes for a wedding in December'])
        ->call('submit')
        ->assertHasNoErrors();
});

it('reports every missing business detail', function () {
    Livewire::test(WholesalePage::class)
        ->call('addToEnquiry', 'MG-KK-3')
        ->call('submit')
        ->assertHasErrors([
            'form.businessName' => 'required',
            'form.contactName' => 'required',
            'form.phone' => 'required',
            'form.city' => 'required',
            'form.pincode' => 'required',
        ]);
});

it('rejects invalid enquiry details', function (string $field, string $value) {
    $test = Livewire::test(WholesalePage::class)->call('addToEnquiry', 'MG-KK-3');

    fillEnquiry($test, [$field => $value])
        ->call('submit')
        ->assertHasErrors([$field]);
})->with([
    'bad GSTIN' => ['form.gstin', 'GST12345'],
    'bad email' => ['form.email', 'not-an-email'],
    'bad phone' => ['form.phone', '12345'],
    'bad pincode' => ['form.pincode', '80001'],
    'past date' => ['form.neededBy', '2020-01-01'],
    'unknown business type' => ['form.businessType', 'pirate'],
]);

it('limits how often enquiries can be sent', function () {
    $key = 'wholesale-enquiry:'.session()->getId();
    foreach (range(1, 3) as $attempt) {
        RateLimiter::hit($key, 600);
    }

    fillEnquiry(Livewire::test(WholesalePage::class), ['form.message' => '200 sweet boxes for a wedding in December'])
        ->call('submit')
        ->assertHasErrors(['form.businessName'])
        ->assertSet('submittedReference', null);
});

it('prefills contact details for a signed-in customer', function () {
    signInDemoCustomer();

    Livewire::test(WholesalePage::class)
        ->assertSet('form.contactName', 'Priya Sharma')
        ->assertSet('form.email', 'priya.sharma@example.com')
        ->assertSet('form.phone', '9830012345');
});

it('keeps the submitted reference out of the browser’s reach', function () {
    Livewire::test(WholesalePage::class)
        ->set('submittedReference', 'WQ-9999');
})->throws(CannotUpdateLockedPropertyException::class);
