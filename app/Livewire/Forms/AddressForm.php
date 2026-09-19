<?php

namespace App\Livewire\Forms;

use App\Models\Address;
use App\Rules\IndianMobile;
use App\Rules\ServedPincode;
use App\Support\IndianPhone;
use App\Support\IndianStates;
use App\Support\ShopSettings;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AddressForm extends Form
{
    public const LABELS = ['Home', 'Work', 'Other'];

    public ?int $id = null;

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public string $line1 = '';

    public string $line2 = '';

    public string $landmark = '';

    public string $city = '';

    public string $state = 'Bihar';

    public string $pincode = '';

    public bool $isDefault = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'label' => ['required', Rule::in(self::LABELS)],
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20', new IndianMobile],
            'line1' => ['required', 'string', 'max:120'],
            'line2' => ['nullable', 'string', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:60'],
            'state' => ['required', Rule::in(IndianStates::ALL)],
            'pincode' => ['required', 'string', new ServedPincode(app(ShopSettings::class))],
            'isDefault' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Enter the name of the person receiving the order.',
            'phone.required' => 'Enter a mobile number for delivery.',
            'line1.required' => 'Enter the house or flat number and building.',
            'city.required' => 'Enter the city or town.',
            'pincode.required' => 'Enter a 6-digit pincode.',
        ];
    }

    /**
     * Starts a new address, pre-filled with the customer's details.
     */
    public function start(string $name, ?string $phone, ?string $city): void
    {
        $this->reset();
        $this->resetValidation();
        $this->name = $name;
        $this->phone = (string) $phone;
        $this->city = (string) $city;
    }

    public function fillFrom(Address $address): void
    {
        $this->resetValidation();
        $this->id = $address->id;
        $this->label = $address->label;
        $this->name = $address->recipient_name;
        $this->phone = $address->phone;
        $this->line1 = $address->line1;
        $this->line2 = (string) $address->line2;
        $this->landmark = (string) $address->landmark;
        $this->city = $address->city;
        $this->state = $address->state;
        $this->pincode = $address->pincode;
        $this->isDefault = $address->is_default;
    }

    /**
     * Validates and returns the data to store.
     *
     * @return array{label: string, recipient_name: string, phone: string, line1: string, line2: string|null, landmark: string|null, city: string, state: string, pincode: string, is_default: bool}
     */
    public function payload(): array
    {
        foreach (['name', 'line1', 'line2', 'landmark', 'city', 'pincode'] as $field) {
            $this->{$field} = trim($this->{$field});
        }

        $this->validate();

        return [
            'label' => $this->label,
            'recipient_name' => $this->name,
            'phone' => IndianPhone::normalize($this->phone),
            'line1' => $this->line1,
            'line2' => $this->line2 !== '' ? $this->line2 : null,
            'landmark' => $this->landmark !== '' ? $this->landmark : null,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'is_default' => $this->isDefault,
        ];
    }
}
