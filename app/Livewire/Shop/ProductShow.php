<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCatalog;
use App\Support\Demo\DemoCategory;
use App\Support\Demo\DemoProduct;
use App\Support\Demo\DemoVariant;
use App\Support\Demo\DemoWholesale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductShow extends Component
{
    use AddsToCart;

    #[Locked]
    public string $slug;

    #[Url(as: 'option', except: '')]
    public string $sku = '';

    public int $quantity = 1;

    public function mount(string $product): void
    {
        $found = DemoCatalog::product($product);
        abort_if($found === null, 404);

        $this->slug = $product;

        if ($found->variant($this->sku) === null) {
            $this->sku = $found->hasChoices() ? $found->defaultVariant()->sku : '';
        }
    }

    public function selectVariant(string $sku): void
    {
        if ($this->product()->variant($sku) !== null) {
            $this->sku = $sku;
            $this->quantity = 1;
        }
    }

    public function add(): void
    {
        $this->addToCart($this->selectedVariant()->sku, $this->clampedQuantity());
    }

    /**
     * Adds the minimum wholesale quantity, so the slab price applies straight away.
     */
    public function addWholesaleMinimum(): void
    {
        $item = DemoWholesale::item($this->selectedVariant()->sku);

        if ($item === null) {
            return;
        }

        $this->quantity = max($this->quantity, $item->moq());
        $this->addToCart($item->sku(), $this->quantity);
    }

    public function buyNow(): mixed
    {
        $variant = $this->selectedVariant();

        if (! $variant->inStock()) {
            return null;
        }

        $cart = app(DemoCart::class);
        if ($cart->quantityOf($variant->sku) < $this->clampedQuantity()) {
            $cart->setQuantity($variant->sku, $this->clampedQuantity());
        }

        return $this->redirectRoute('cart.show', navigate: false);
    }

    public function render(): View
    {
        $product = $this->product();
        $variant = $this->selectedVariant();
        $root = DemoCatalog::category($product->category);
        $section = DemoCatalog::category($product->subcategory);

        return view('livewire.shop.product-show', [
            'product' => $product,
            'variant' => $variant,
            'maxQuantity' => app(DemoCart::class)->ceilingFor($variant->sku, $variant),
            'inBag' => app(DemoCart::class)->quantityOf($variant->sku),
            'wholesale' => DemoWholesale::item($variant->sku),
            'similar' => DemoCatalog::similar($product),
            'breadcrumb' => $this->breadcrumb($product, $root, $section),
        ])->layout('layouts::shop', [
            'title' => "{$product->name} by {$product->brand}",
            'description' => Str::limit($product->summary, 155),
            'active' => 'categories',
            'ogType' => 'product',
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function breadcrumb(DemoProduct $product, ?DemoCategory $root, ?DemoCategory $section): array
    {
        $items = ['Home' => route('shop.home')];

        if ($root) {
            $items[$root->name] = route('shop.category', $root->slug);
        }
        if ($section) {
            $items[$section->name] = route('shop.category', $section->slug);
        }
        $items[$product->name] = null;

        return $items;
    }

    private function product(): DemoProduct
    {
        return DemoCatalog::product($this->slug) ?? abort(404);
    }

    private function selectedVariant(): DemoVariant
    {
        $product = $this->product();

        return $product->variant($this->sku) ?? $product->defaultVariant();
    }

    private function clampedQuantity(): int
    {
        $variant = $this->selectedVariant();

        return max(1, min($this->quantity, app(DemoCart::class)->ceilingFor($variant->sku, $variant)));
    }
}
