<?php

use App\Enums\DeliveryStep;
use App\Livewire\Delivery\DeliveryShow;
use App\Support\Demo\DemoDeliveries;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    signInDemoDeliveryBoy();
});

it('shows the customer, address, items and the cash to collect', function () {
    $this->get('/delivery/ORD-10245')
        ->assertOk()
        ->assertSee('Priya Sharma')
        ->assertSee('Flat 3B, Shanti Apartments')
        ->assertSee('800001')
        ->assertSee('+91 98300 12345')
        ->assertSee('Please call before arriving. Gate code 4411.')
        ->assertSee('Velvet matte lipstick')
        ->assertSee('Collect in cash')
        ->assertSee('₹1,748')
        ->assertSee('On the way');
});

it('says an order is already paid when it is a UPI order', function () {
    $this->get('/delivery/ORD-10252')
        ->assertSee('Already paid by UPI')
        ->assertDontSee('Collect in cash');
});

it('returns 404 for an order that is not assigned to this delivery boy', function () {
    $this->get('/delivery/ORD-99999')->assertNotFound();
});

it('walks a delivery from accepted to at the address', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10250'])
        ->call('advance')
        ->assertDispatched('toast', tone: 'success')
        ->call('advance')
        ->assertSee('At the address');

    expect(app(DemoDeliveries::class)->find('ORD-10250')->step)->toBe(DeliveryStep::Reached);
});

it('confirms a cash delivery with the customer code and the cash collected', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])
        ->assertSet('cash', '1398')
        ->set('code', '905617')
        ->call('confirmDelivery')
        ->assertHasNoErrors()
        ->assertSee('Delivered')
        ->assertDispatched('toast', tone: 'success');

    $job = app(DemoDeliveries::class)->find('ORD-10246');
    expect($job->step)->toBe(DeliveryStep::Delivered)
        ->and($job->cashCollectedPaise)->toBe(139800)
        ->and($job->cashToHandOver())->toBe(139800);
});

it('counts down the tries for a wrong delivery code and then stops', function () {
    $test = Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])->set('code', '000000');

    $test->call('confirmDelivery')->assertHasErrors(['code'])->assertSee('2 tries left')->assertSet('code', '');
    $test->set('code', '111111')->call('confirmDelivery')->assertSee('1 try left');
    $test->set('code', '222222')->call('confirmDelivery')->assertSee('Too many wrong codes');
    $test->set('code', '905617')->call('confirmDelivery')->assertHasErrors(['code']);

    expect(app(DemoDeliveries::class)->find('ORD-10246')->step)->toBe(DeliveryStep::Reached);
});

it('insists on the full cash amount', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])
        ->set('code', '905617')
        ->set('cash', '1000')
        ->call('confirmDelivery')
        ->assertHasErrors(['cash'])
        ->assertSee('Collect the full ₹1,398');

    expect(app(DemoDeliveries::class)->find('ORD-10246')->step)->toBe(DeliveryStep::Reached);
});

it('needs the code to be six digits', function (string $code) {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])
        ->set('code', $code)
        ->call('confirmDelivery')
        ->assertHasErrors(['code']);
})->with(['empty' => '', 'too short' => '9056', 'not digits' => 'abcdef']);

it('records a failed delivery with a reason the customer can read', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])
        ->set('failureReason', 'nobody_home')
        ->call('reportFailure')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', 'delivery-failed')
        ->assertSee('Could not deliver');

    $job = app(DemoDeliveries::class)->find('ORD-10246');
    expect($job->step)->toBe(DeliveryStep::Failed)
        ->and($job->failureReason->customerMessage())->toContain('Nobody was home');
});

it('asks for a note when the reason alone is not enough', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])
        ->set('failureReason', 'address_wrong')
        ->call('reportFailure')
        ->assertHasErrors(['failureNote' => 'required'])
        ->set('failureNote', 'Flat 8C does not exist in this building')
        ->call('reportFailure')
        ->assertHasNoErrors();

    expect(app(DemoDeliveries::class)->find('ORD-10246')->failureNote)->toBe('Flat 8C does not exist in this building');
});

it('needs a reason before reporting a problem', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])
        ->call('reportFailure')
        ->assertHasErrors(['failureReason' => 'required']);
});

it('will not let a new delivery be failed before it is accepted', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10252'])
        ->set('failureReason', 'nobody_home')
        ->call('reportFailure')
        ->assertHasErrors(['failureReason']);

    expect(app(DemoDeliveries::class)->find('ORD-10252')->step)->toBe(DeliveryStep::Assigned);
});

it('offers no further action once a delivery is finished', function () {
    $this->get('/delivery/ORD-10243')
        ->assertSee('Delivered at')
        ->assertDontSee('Could not deliver</button>', false);
});

it('keeps the order number out of the browser’s reach', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10245'])
        ->set('number', 'ORD-10250');
})->throws(CannotUpdateLockedPropertyException::class);
