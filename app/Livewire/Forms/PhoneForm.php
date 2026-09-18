<?php

namespace App\Livewire\Forms;

use App\Rules\IndianMobile;
use App\Support\IndianPhone;
use Livewire\Form;

class PhoneForm extends Form
{
    public string $phone = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', new IndianMobile],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.required' => 'Enter your mobile number so the delivery partner can reach you.',
        ];
    }

    /**
     * Validates and returns the 10-digit number.
     */
    public function validated(): string
    {
        $this->validate();

        return IndianPhone::normalize($this->phone);
    }
}
