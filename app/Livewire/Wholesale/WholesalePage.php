<?php

namespace App\Livewire\Wholesale;

use App\Livewire\Forms\WholesaleEnquiryForm;
use App\Support\Demo\DemoCustomer;
use App\Support\Demo\DemoWholesale;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Wholesale: bulk price slabs, an enquiry list and a quote request form.
 */
class WholesalePage extends Component
{
    public const CATEGORIES = [
        '' => 'All products',
        'confectionery' => 'Sweets and chocolates',
        'gifts' => 'Gifts and hampers',
        'cosmetics' => 'Cosmetics',
    ];

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, int|string> Quantities typed next to each product */
    public array $quantities = [];

    /** @var array<string, int|string> Quantities in the enquiry list */
    public array $listQuantities = [];

    public WholesaleEnquiryForm $form;

    #[Locked]
    public ?string $submittedReference = null;

    public function mount(DemoWholesale $wholesale, DemoCustomer $customer): void
    {
        foreach (DemoWholesale::items() as $item) {
            $this->quantities[$item->sku()] = $item->moq();
        }

        $this->syncList($wholesale);

        if ($customer->isSignedIn()) {
            $profile = $customer->profile();
            $this->form->contactName = $profile['name'];
            $this->form->email = $profile['email'];
            $this->form->phone = (string) $profile['phone'];
        }
    }

    public function addToEnquiry(string $sku, DemoWholesale $wholesale): void
    {
        $item = DemoWholesale::item($sku);

        if ($item === null) {
            return;
        }

        $requested = (int) ($this->quantities[$sku] ?? $item->moq());
        $stored = $wholesale->add($sku, max($item->moq(), $requested));
        $this->quantities[$sku] = $item->moq();
        $this->syncList($wholesale);

        $this->dispatch('toast', message: "{$item->product->name}: {$stored} in your enquiry at ".Money::format($item->unitPriceFor($stored)).' each.', tone: 'success');
    }

    public function updatedListQuantities(mixed $value, string $sku): void
    {
        $wholesale = app(DemoWholesale::class);
        $item = DemoWholesale::item($sku);

        if ($item === null || $wholesale->quantityOf($sku) === 0) {
            $this->syncList($wholesale);

            return;
        }

        $requested = (int) $value;
        $stored = $wholesale->setQuantity($sku, max(1, $requested));

        if ($stored !== $requested) {
            $this->dispatch('toast', message: $requested < $item->moq()
                ? "The minimum order for {$item->product->name} is {$item->moq()}."
                : 'Quantity updated.', tone: $requested < $item->moq() ? 'warning' : 'info');
        }

        $this->syncList($wholesale);
    }

    public function removeFromEnquiry(string $sku, DemoWholesale $wholesale): void
    {
        $wholesale->remove($sku);
        $this->syncList($wholesale);
    }

    public function submit(DemoWholesale $wholesale): void
    {
        $key = 'wholesale-enquiry:'.session()->getId();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'form.businessName' => 'You have sent several enquiries already. Please wait '.ceil(RateLimiter::availableIn($key) / 60).' minutes, or message us on WhatsApp.',
            ]);
        }

        $details = $this->form->payload($wholesale->count() > 0);
        RateLimiter::hit($key, 600);

        $this->submittedReference = $wholesale->submit($details);
        $this->syncList($wholesale);
        $this->dispatch('wholesale-submitted');
    }

    public function startNewEnquiry(): void
    {
        $this->submittedReference = null;
        $this->form->reset('message', 'neededBy');
    }

    public function render(DemoWholesale $wholesale): View
    {
        $search = Str::limit(trim($this->search), 60, '');

        return view('livewire.wholesale.wholesale-page', [
            'items' => DemoWholesale::query(
                array_key_exists($this->category, self::CATEGORIES) && $this->category !== '' ? $this->category : null,
                $search,
            ),
            'featured' => DemoWholesale::item('MG-KK-3') ?? DemoWholesale::items()[0],
            'lines' => $wholesale->lines(),
            'estimate' => $wholesale->estimate(),
            'categories' => self::CATEGORIES,
            'businessTypes' => DemoWholesale::BUSINESS_TYPES,
            'submitted' => $this->submittedReference ? $wholesale->enquiry($this->submittedReference) : null,
        ])->layout('layouts::shop', [
            'title' => 'Wholesale',
            'description' => 'Wholesale prices on sweets, gifts and cosmetics for shops, events and corporate gifting.',
            'active' => 'wholesale',
        ]);
    }

    private function syncList(DemoWholesale $wholesale): void
    {
        $this->listQuantities = collect($wholesale->lines())
            ->mapWithKeys(fn (array $line): array => [$line['item']->sku() => $line['quantity']])
            ->all();
    }
}
