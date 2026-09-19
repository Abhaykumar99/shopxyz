<?php

use App\Livewire\Account\AddressBook;
use App\Models\Address;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->customer = signInCustomer();

    $this->home = Address::factory()->default()->for($this->customer)->create([
        'label' => 'Home',
        'line1' => 'Flat 3B, Shanti Apartments',
        'pincode' => '800001',
    ]);

    $this->work = Address::factory()->for($this->customer)->create([
        'label' => 'Work',
        'line1' => '2nd floor, Lalit Bhawan',
        'pincode' => '800001',
    ]);
});

it('lists saved addresses with the default first', function () {
    Livewire::test(AddressBook::class)
        ->assertSeeInOrder(['Default', 'Flat 3B, Shanti Apartments', '2nd floor, Lalit Bhawan']);
});

it('adds a new address', function () {
    Livewire::test(AddressBook::class)
        ->call('create')
        ->assertDispatched('open-modal', 'address-form')
        ->set('addressForm.label', 'Other')
        ->set('addressForm.line1', 'Sita Niwas, Road 4')
        ->set('addressForm.landmark', 'Hanuman temple')
        ->set('addressForm.pincode', '800020')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', 'address-form')
        ->assertSee('Sita Niwas, Road 4')
        ->assertSee('Near Hanuman temple');

    expect($this->customer->addresses()->count())->toBe(3);
});

it('reports every missing required field', function () {
    Livewire::test(AddressBook::class)
        ->call('create')
        ->set('addressForm.name', '')
        ->set('addressForm.phone', '')
        ->set('addressForm.city', '')
        ->call('save')
        ->assertHasErrors([
            'addressForm.name' => 'required',
            'addressForm.phone' => 'required',
            'addressForm.line1' => 'required',
            'addressForm.city' => 'required',
            'addressForm.pincode' => 'required',
        ])
        ->assertSee('Enter the name of the person receiving the order.')
        ->assertSee('Enter the house or flat number and building.');

    expect($this->customer->addresses()->count())->toBe(2);
});

it('rejects values outside the allowed lists', function (string $field, string $value) {
    Livewire::test(AddressBook::class)
        ->call('create')
        ->set('addressForm.line1', 'House 1')
        ->set('addressForm.pincode', '800001')
        ->set("addressForm.{$field}", $value)
        ->call('save')
        ->assertHasErrors(["addressForm.{$field}"]);
})->with([
    'unknown label' => ['label', 'Palace'],
    'unknown state' => ['state', 'Atlantis'],
    'short pincode' => ['pincode', '8000'],
    'overlong name' => ['name', str_repeat('a', 81)],
]);

it('edits an address', function () {
    Livewire::test(AddressBook::class)
        ->call('edit', $this->work->id)
        ->assertSet('addressForm.line1', '2nd floor, Lalit Bhawan')
        ->set('addressForm.line1', '3rd floor, Lalit Bhawan')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->work->fresh()->line1)->toBe('3rd floor, Lalit Bhawan')
        ->and($this->customer->addresses()->count())->toBe(2);
});

it('makes another address the default, and only one at a time', function () {
    Livewire::test(AddressBook::class)->call('makeDefault', $this->work->id);

    expect($this->work->fresh()->is_default)->toBeTrue()
        ->and($this->home->fresh()->is_default)->toBeFalse();
});

it('deletes an address after confirmation and moves the default', function () {
    Livewire::test(AddressBook::class)
        ->call('confirmDelete', $this->home->id)
        ->assertSet('deletingId', $this->home->id)
        ->assertDispatched('open-modal', 'delete-address')
        ->call('delete')
        ->assertDispatched('close-modal', 'delete-address');

    expect($this->customer->addresses()->count())->toBe(1)
        ->and($this->work->fresh()->is_default)->toBeTrue();
});

it('never reaches another customer\'s address', function () {
    $stranger = Address::factory()->default()->for(User::factory()->googleCustomer())->create();

    Livewire::test(AddressBook::class)
        ->call('confirmDelete', $stranger->id)
        ->assertSet('deletingId', null)
        ->call('edit', $stranger->id)
        ->assertSet('addressForm.id', null)
        ->call('makeDefault', $stranger->id);

    expect(Address::find($stranger->id))->not->toBeNull()
        ->and($this->customer->addresses()->count())->toBe(2);
});

it('makes the first address the default automatically', function () {
    $fresh = signInCustomer();

    Livewire::test(AddressBook::class)
        ->call('create')
        ->set('addressForm.line1', 'First address')
        ->set('addressForm.pincode', '800001')
        ->call('save')
        ->assertHasNoErrors();

    expect($fresh->addresses()->sole()->is_default)->toBeTrue();
});
