<?php

use App\Livewire\Account\AddressBook;
use App\Support\Demo\DemoCustomer;
use Livewire\Livewire;

beforeEach(function () {
    signInDemoCustomer();
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

    expect(app(DemoCustomer::class)->addresses())->toHaveCount(3);
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

    expect(app(DemoCustomer::class)->addresses())->toHaveCount(2);
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
        ->call('edit', 'addr_work')
        ->assertSet('addressForm.line1', '2nd floor, Lalit Bhawan')
        ->set('addressForm.line1', '3rd floor, Lalit Bhawan')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(DemoCustomer::class)->address('addr_work')->line1)->toBe('3rd floor, Lalit Bhawan')
        ->and(app(DemoCustomer::class)->addresses())->toHaveCount(2);
});

it('makes another address the default', function () {
    Livewire::test(AddressBook::class)->call('makeDefault', 'addr_work');

    expect(app(DemoCustomer::class)->defaultAddress()->id)->toBe('addr_work');
});

it('deletes an address after confirmation and moves the default', function () {
    Livewire::test(AddressBook::class)
        ->call('confirmDelete', 'addr_home')
        ->assertSet('deletingId', 'addr_home')
        ->assertDispatched('open-modal', 'delete-address')
        ->call('delete')
        ->assertDispatched('close-modal', 'delete-address');

    expect(app(DemoCustomer::class)->addresses())->toHaveCount(1)
        ->and(app(DemoCustomer::class)->defaultAddress()->id)->toBe('addr_work');
});

it('ignores addresses that do not belong to the customer', function () {
    Livewire::test(AddressBook::class)
        ->call('confirmDelete', 'addr_someone_else')
        ->assertSet('deletingId', null)
        ->set('deletingId', 'addr_someone_else')
        ->call('delete');

    expect(app(DemoCustomer::class)->addresses())->toHaveCount(2);
});
