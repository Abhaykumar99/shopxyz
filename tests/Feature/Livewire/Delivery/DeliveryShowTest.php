<?php

use App\Enums\DeliveryStep;
use App\Livewire\Delivery\DeliveryShow;
use App\Support\Demo\DemoDeliveries;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    signInDemoDeliveryBoy();
});

it('shows the boxes, customer, address, items and the cash to collect', function () {
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
        ->assertSee('On the way')
        ->assertSee('2 boxes');
});

it('says an order is already paid when it is a UPI order', function () {
    $this->get('/delivery/ORD-10252')
        ->assertSee('Already paid by UPI')
        ->assertDontSee('Collect in cash');
});

it('returns 404 for an order that is not assigned to this delivery boy', function () {
    $this->get('/delivery/ORD-99999')->assertNotFound();
});

it('collects every box with the pickup code on its label before going out for delivery', function () {
    $test = Livewire::test(DeliveryShow::class, ['order' => 'ORD-10250'])
        ->assertSee('Collect 2 boxes')
        ->assertSee('PKG-10250-1')
        ->assertSee('Box 1 of 2')
        ->assertDontSee('204877')
        ->set('pickupCodes.PKG-10250-1', '204877')
        ->call('verifyPickup', 'PKG-10250-1')
        ->assertHasNoErrors()
        ->assertDispatched('toast', tone: 'success')
        ->assertSee('1 of 2 verified');

    expect(app(DemoDeliveries::class)->find('ORD-10250')->step)->toBe(DeliveryStep::Accepted);

    $test->set('pickupCodes.PKG-10250-2', '913526')
        ->call('verifyPickup', 'PKG-10250-2')
        ->assertHasNoErrors()
        ->assertSee('On the way');

    $job = app(DemoDeliveries::class)->find('ORD-10250');
    expect($job->step)->toBe(DeliveryStep::PickedUp)
        ->and($job->allPickedUp())->toBeTrue()
        ->and($job->timeFor(DeliveryStep::PickedUp))->not->toBeNull();
});

it('never shows a pickup code or the customer OTP in the panel', function () {
    $this->get('/delivery/ORD-10250')->assertDontSee('204877')->assertDontSee('770143');
    $this->get('/delivery/ORD-10246')->assertDontSee('905617')->assertDontSee('884120');
});

it('counts down the tries for a wrong pickup code', function () {
    $test = Livewire::test(DeliveryShow::class, ['order' => 'ORD-10250']);

    $test->set('pickupCodes.PKG-10250-1', '000000')
        ->call('verifyPickup', 'PKG-10250-1')
        ->assertHasErrors(['pickupCodes.PKG-10250-1'])
        ->assertSee('does not match Box 1 of 2')
        ->assertSee('2 tries left');

    $test->set('pickupCodes.PKG-10250-1', '111111')->call('verifyPickup', 'PKG-10250-1')->assertSee('1 try left');
    $test->set('pickupCodes.PKG-10250-1', '222222')->call('verifyPickup', 'PKG-10250-1')->assertSee('Ask the shop to check the boxes');
    $test->set('pickupCodes.PKG-10250-1', '204877')->call('verifyPickup', 'PKG-10250-1')->assertHasErrors(['pickupCodes.PKG-10250-1']);

    expect(app(DemoDeliveries::class)->find('ORD-10250')->allPickedUp())->toBeFalse();
});

it('needs a six digit pickup code', function (string $code) {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10250'])
        ->set('pickupCodes.PKG-10250-1', $code)
        ->call('verifyPickup', 'PKG-10250-1')
        ->assertHasErrors(['pickupCodes.PKG-10250-1']);
})->with(['empty' => '', 'too short' => '2048', 'not digits' => 'abc123']);

it('does not offer pickup codes once the boxes are collected', function () {
    $this->get('/delivery/ORD-10245')
        ->assertSee('All verified at pickup')
        ->assertDontSee('Enter the pickup code printed on each box label');
});

it('walks from picked up to at the address with a tap', function () {
    Livewire::test(DeliveryShow::class, ['order' => 'ORD-10245'])
        ->call('advance')
        ->assertDispatched('toast', tone: 'success')
        ->assertSee('At the address');

    expect(app(DemoDeliveries::class)->find('ORD-10245')->step)->toBe(DeliveryStep::Reached);
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

it('counts down the tries for a wrong delivery OTP and then stops', function () {
    $test = Livewire::test(DeliveryShow::class, ['order' => 'ORD-10246'])->set('code', '000000');

    $test->call('confirmDelivery')->assertHasErrors(['code'])->assertSee('That OTP is not right')->assertSee('2 tries left')->assertSet('code', '');
    $test->set('code', '111111')->call('confirmDelivery')->assertSee('1 try left');
    $test->set('code', '222222')->call('confirmDelivery')->assertSee('Too many wrong OTPs');
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
