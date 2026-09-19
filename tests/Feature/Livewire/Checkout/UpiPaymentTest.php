<?php

use App\Enums\PaymentStatus;
use App\Livewire\Checkout\UpiPayment;
use App\Support\Demo\DemoOrders;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

beforeEach(function () {
    seedCatalog();
    signInCustomer();
});

it('shows the amount, UPI ID and a pay-in-app link', function () {
    $this->get(route('orders.pay', 'ORD-10247'))
        ->assertOk()
        ->assertSee('Pay ₹798 by UPI')
        ->assertSee('demo.shop@okaxis')
        ->assertSee('upi://pay?pa=demo.shop%40okaxis', false)
        ->assertSee('We couldn&#039;t match your last payment', false);
});

it('sends orders that need no payment proof to the order page', function (string $number) {
    Livewire::test(UpiPayment::class, ['order' => $number])
        ->assertRedirect(route('account.order', $number));
})->with(['cash on delivery' => 'ORD-10245', 'already checking' => 'ORD-10248']);

it('returns 404 for an unknown order', function () {
    $this->get(route('orders.pay', 'ORD-00000'))->assertNotFound();
});

it('requires a screenshot and a 12-digit UTR', function () {
    Livewire::test(UpiPayment::class, ['order' => 'ORD-10247'])
        ->set('proof.utr', '1234')
        ->call('submit')
        ->assertHasErrors(['proof.screenshot' => 'required', 'proof.utr' => 'regex'])
        ->assertSee('Upload a screenshot of the successful payment.')
        ->assertSee('The UTR number is 12 digits.');
});

it('accepts only image screenshots up to 4 MB', function (UploadedFile $file, string $message) {
    Livewire::test(UpiPayment::class, ['order' => 'ORD-10247'])
        ->set('proof.screenshot', $file)
        ->assertHasErrors(['proof.screenshot'])
        ->assertSee($message);

    expect(app(DemoOrders::class)->find('ORD-10247')->paymentStatus)->toBe(PaymentStatus::Rejected);
})->with([
    'pdf' => [fn () => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'), 'Upload a JPG, PNG or WebP image.'],
    'svg' => [fn () => UploadedFile::fake()->create('receipt.svg', 10, 'image/svg+xml'), 'Upload a JPG, PNG or WebP image.'],
]);

it('rejects screenshots larger than 4 MB', function () {
    Livewire::test(UpiPayment::class, ['order' => 'ORD-10247'])
        ->set('proof.screenshot', UploadedFile::fake()->image('receipt.png')->size(5000))
        ->assertHasErrors(['proof.screenshot']);
});

it('explains upload problems in plain words', function (UploadedFile $file, string $message) {
    $validator = Validator::make(['files' => [$file]], ['files.*' => config('livewire.temporary_file_upload.rules')]);

    expect($validator->errors()->first('files.0'))->toBe($message);
})->with([
    'wrong type' => [fn () => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'), 'Upload a JPG, PNG or WebP image.'],
    'too large' => [fn () => UploadedFile::fake()->image('receipt.png')->size(5000), 'The image must be 4 MB or smaller.'],
]);

it('rejects a UTR already used on another order', function () {
    Livewire::test(UpiPayment::class, ['order' => 'ORD-10247'])
        ->set('proof.screenshot', UploadedFile::fake()->image('paid.jpg'))
        ->set('proof.utr', '412345678901')
        ->call('submit')
        ->assertHasErrors(['proof.utr'])
        ->assertSee('This UTR number is already used on another order.');
});

it('sends valid payment details for verification', function () {
    Livewire::test(UpiPayment::class, ['order' => 'ORD-10247'])
        ->set('proof.screenshot', UploadedFile::fake()->image('paid.jpg'))
        ->set('proof.utr', '5555 5555 5555')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('orders.placed', 'ORD-10247'));

    expect(app(DemoOrders::class)->find('ORD-10247'))
        ->paymentStatus->toBe(PaymentStatus::PendingVerification)
        ->utr->toBe('555555555555');
});

it('slows down repeated attempts', function () {
    RateLimiter::hit('upi-proof:'.session()->getId(), 600);
    foreach (range(1, 4) as $attempt) {
        RateLimiter::hit('upi-proof:'.session()->getId(), 600);
    }

    Livewire::test(UpiPayment::class, ['order' => 'ORD-10247'])
        ->set('proof.utr', '555555555555')
        ->call('submit')
        ->assertHasErrors(['proof.utr'])
        ->assertSee('Too many attempts.');
});
