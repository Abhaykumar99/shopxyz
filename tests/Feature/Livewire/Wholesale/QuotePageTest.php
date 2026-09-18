<?php

use App\Livewire\Wholesale\QuotePage;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoWholesale;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

function validQuote(): array
{
    return [
        'form.businessName' => 'Sharma Sweets and Gifts',
        'form.contactName' => 'Ravi Sharma',
        'form.phone' => '98300 11111',
        'form.businessType' => 'retail',
        'form.city' => 'Patna',
        'form.pincode' => '800001',
        'form.message' => '200 sweet boxes for a wedding in December, with printed name cards',
    ];
}

function fillQuote($test, array $overrides = [])
{
    foreach ([...validQuote(), ...$overrides] as $field => $value) {
        $test->set($field, $value);
    }

    return $test;
}

it('presents the quote as the optional route, not the way to order', function () {
    $this->get('/wholesale/quote')
        ->assertOk()
        ->assertSee('Request a wholesale quote')
        ->assertSee('When a quote helps')
        ->assertSee('you don\'t need a quote', false)
        ->assertSee('href="'.route('wholesale.index').'"', false);
});

it('sends a quote request and shows the reference', function () {
    fillQuote(Livewire::test(QuotePage::class), [
        'form.gstin' => '10abcde1234f1z5',
        'form.neededBy' => now()->addWeek()->toDateString(),
    ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submittedReference', 'WQ-5101')
        ->assertSee('Thank you, your request is with us')
        ->assertSee('WQ-5101')
        ->assertSee('+91 98300 11111');

    $enquiry = app(DemoWholesale::class)->enquiry('WQ-5101');
    expect($enquiry['details'])->toMatchArray(['gstin' => '10ABCDE1234F1Z5', 'phone' => '9830011111'])
        ->and($enquiry['items'])->toBe([]);
});

it('attaches the bag at its current prices and leaves it untouched', function () {
    fillDemoCart(['MG-KK-3' => 20, 'BB-LIP-1' => 1]);

    fillQuote(Livewire::test(QuotePage::class))
        ->assertSee('in my bag')
        ->assertSee('2 products, about ₹17,949')
        ->call('submit')
        ->assertHasNoErrors();

    $enquiry = app(DemoWholesale::class)->enquiry('WQ-5101');
    expect($enquiry['items'][0])->toMatchArray(['sku' => 'MG-KK-3', 'quantity' => 20, 'unit_paise' => 88000])
        ->and($enquiry['estimate'])->toBe(20 * 88000 + 34900)
        ->and(app(DemoCart::class)->count())->toBe(21);
});

it('can send a request without the bag', function () {
    fillDemoCart(['MG-KK-3' => 20]);

    fillQuote(Livewire::test(QuotePage::class))
        ->set('attachBag', false)
        ->call('submit')
        ->assertHasNoErrors();

    expect(app(DemoWholesale::class)->enquiry('WQ-5101')['items'])->toBe([]);
});

it('always needs to know what the buyer is asking for', function () {
    fillQuote(Livewire::test(QuotePage::class), ['form.message' => ''])
        ->call('submit')
        ->assertHasErrors(['form.message' => 'required'])
        ->assertSee('Tell us what you need, and we will come back with a quote.');
});

it('reports every missing business detail', function () {
    Livewire::test(QuotePage::class)
        ->call('submit')
        ->assertHasErrors([
            'form.businessName' => 'required',
            'form.contactName' => 'required',
            'form.phone' => 'required',
            'form.city' => 'required',
            'form.pincode' => 'required',
            'form.message' => 'required',
        ]);
});

it('rejects invalid quote details', function (string $field, string $value) {
    fillQuote(Livewire::test(QuotePage::class), [$field => $value])
        ->call('submit')
        ->assertHasErrors([$field]);
})->with([
    'bad GSTIN' => ['form.gstin', 'GST12345'],
    'bad email' => ['form.email', 'not-an-email'],
    'bad phone' => ['form.phone', '12345'],
    'bad pincode' => ['form.pincode', '80001'],
    'past date' => ['form.neededBy', '2020-01-01'],
    'unknown business type' => ['form.businessType', 'pirate'],
    'too short a message' => ['form.message', 'need boxes'],
]);

it('limits how often requests can be sent', function () {
    $key = 'wholesale-enquiry:'.session()->getId();
    foreach (range(1, 3) as $attempt) {
        RateLimiter::hit($key, 600);
    }

    fillQuote(Livewire::test(QuotePage::class))
        ->call('submit')
        ->assertHasErrors(['form.businessName'])
        ->assertSet('submittedReference', null);
});

it('prefills contact details for a signed-in customer', function () {
    signInDemoCustomer();

    Livewire::test(QuotePage::class)
        ->assertSet('form.contactName', 'Priya Sharma')
        ->assertSet('form.email', 'priya.sharma@example.com')
        ->assertSet('form.phone', '9830012345');
});

it('keeps the submitted reference out of the browser’s reach', function () {
    Livewire::test(QuotePage::class)
        ->set('submittedReference', 'WQ-9999');
})->throws(CannotUpdateLockedPropertyException::class);
