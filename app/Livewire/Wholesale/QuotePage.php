<?php

namespace App\Livewire\Wholesale;

use App\Livewire\Forms\WholesaleEnquiryForm;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCustomer;
use App\Support\Demo\DemoWholesale;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Optional quote request (ADR-019): for buyers who need custom pricing, packing,
 * branding, quantities beyond the published slabs or a payment arrangement.
 * Everything the catalogue can price is ordered through the bag instead.
 */
class QuotePage extends Component
{
    public WholesaleEnquiryForm $form;

    public bool $attachBag = true;

    #[Locked]
    public ?string $submittedReference = null;

    public function mount(DemoCustomer $customer): void
    {
        if ($customer->isSignedIn()) {
            $profile = $customer->profile();
            $this->form->contactName = $profile['name'];
            $this->form->email = $profile['email'];
            $this->form->phone = (string) $profile['phone'];
        }
    }

    public function submit(DemoWholesale $wholesale, DemoCart $cart): void
    {
        $key = 'wholesale-enquiry:'.session()->getId();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'form.businessName' => 'You have sent several requests already. Please wait '.ceil(RateLimiter::availableIn($key) / 60).' minutes, or message us on WhatsApp.',
            ]);
        }

        $details = $this->form->payload();
        RateLimiter::hit($key, 600);

        $this->submittedReference = $wholesale->submit($details, $this->attachBag ? $cart->lines() : []);
        $this->dispatch('quote-submitted');
    }

    public function startAnother(): void
    {
        $this->submittedReference = null;
        $this->form->reset('message', 'neededBy');
    }

    public function render(DemoWholesale $wholesale, DemoCart $cart, ShopSettings $shop): View
    {
        return view('livewire.wholesale.quote-page', [
            'businessTypes' => DemoWholesale::BUSINESS_TYPES,
            'lines' => $cart->lines(),
            'estimate' => $cart->summary($shop)['subtotal'],
            'submitted' => $this->submittedReference ? $wholesale->enquiry($this->submittedReference) : null,
        ])->layout('layouts::shop', [
            'title' => 'Request a wholesale quote',
            'description' => 'Ask for custom bulk pricing, packing or branding for your shop, event or company.',
            'active' => 'wholesale',
        ]);
    }
}
