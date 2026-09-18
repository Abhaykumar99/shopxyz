<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class PaymentProofForm extends Form
{
    /** @var TemporaryUploadedFile|null */
    public $screenshot = null;

    public string $utr = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'screenshot' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'utr' => ['required', 'string', 'regex:/^\d{12}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'screenshot.required' => 'Upload a screenshot of the successful payment.',
            'screenshot.mimes' => 'Upload the screenshot as a JPG, PNG or WebP image.',
            'screenshot.max' => 'The screenshot must be 4 MB or smaller.',
            'utr.required' => 'Enter the 12-digit UTR number from your UPI app.',
            'utr.regex' => 'The UTR number is 12 digits. You can find it in your UPI app under transaction details.',
        ];
    }

    /**
     * Validates and returns the UTR (digits only).
     */
    public function validatedUtr(): string
    {
        $this->utr = preg_replace('/\s+/', '', $this->utr) ?? '';
        $this->validate();

        return $this->utr;
    }
}
