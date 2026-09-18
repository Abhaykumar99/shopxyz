<?php

namespace App\Livewire\Forms;

use App\Rules\IndianMobile;
use App\Support\Demo\DemoWholesale;
use App\Support\IndianPhone;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class WholesaleEnquiryForm extends Form
{
    public string $businessName = '';

    public string $contactName = '';

    public string $phone = '';

    public string $email = '';

    public string $gstin = '';

    public string $businessType = 'retail';

    public string $city = '';

    public string $pincode = '';

    public string $neededBy = '';

    public string $message = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'businessName' => ['required', 'string', 'max:120'],
            'contactName' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20', new IndianMobile],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'gstin' => ['nullable', 'string', 'regex:/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'businessType' => ['required', Rule::in(array_keys(DemoWholesale::BUSINESS_TYPES))],
            'city' => ['required', 'string', 'max:60'],
            'pincode' => ['required', 'string', 'regex:/^[1-9]\d{5}$/'],
            'neededBy' => ['nullable', 'date_format:Y-m-d', 'after:today'],
            'message' => ['required', 'string', 'min:20', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'businessName.required' => 'Enter your business or organisation name.',
            'contactName.required' => 'Enter the name of the person we should speak to.',
            'phone.required' => 'Enter a mobile number so we can call you with the quote.',
            'email.email' => 'Enter a valid email address, or leave it empty.',
            'gstin.regex' => 'Enter a valid 15-character GSTIN, for example 10ABCDE1234F1Z5, or leave it empty.',
            'city.required' => 'Enter the city for delivery.',
            'pincode.required' => 'Enter a 6-digit pincode.',
            'pincode.regex' => 'Enter a 6-digit pincode.',
            'neededBy.after' => 'Choose a date after today.',
            'message.required' => 'Tell us what you need, and we will come back with a quote.',
            'message.min' => 'Tell us a little more: products, quantities, packing or branding (at least 20 characters).',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function payload(): array
    {
        $this->gstin = Str::upper(str_replace(' ', '', $this->gstin));
        foreach (['businessName', 'contactName', 'email', 'city', 'pincode', 'message'] as $field) {
            $this->{$field} = trim($this->{$field});
        }

        $this->validate();

        return [
            'business_name' => $this->businessName,
            'contact_name' => $this->contactName,
            'phone' => IndianPhone::normalize($this->phone),
            'email' => $this->email ?: null,
            'gstin' => $this->gstin ?: null,
            'business_type' => $this->businessType,
            'city' => $this->city,
            'pincode' => $this->pincode,
            'needed_by' => $this->neededBy ?: null,
            'message' => $this->message ?: null,
        ];
    }
}
