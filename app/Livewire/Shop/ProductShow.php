<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart\Bag;
use App\Support\Catalog\WholesaleItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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
        $this->slug = $product;

        $found = $this->product();

        if ($found->variants->firstWhere('sku', $this->sku) === null) {
            $this->sku = $found->hasChoices() ? (string) $found->firstVariant()?->sku : '';
        }
    }

    public function selectVariant(string $sku): void
    {
        // Only a SKU belonging to this product; anything else is ignored.
        if ($this->product()->variants->firstWhere('sku', $sku) !== null) {
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
        $item = WholesaleItem::for($this->selectedVariant());

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

        $bag = app(Bag::class);
        $wanted = $this->clampedQuantity();
        $shortfall = $wanted - $bag->quantityOf($variant->sku);

        // Adding rather than setting, because changing a quantity never creates
        // a line that is not in the bag yet.
        if ($shortfall > 0) {
            $bag->add($variant, $shortfall);
        }

        return $this->redirectRoute('cart.show', navigate: false);
    }

    public function render(): View
    {
        $product = $this->product();
        $variant = $this->selectedVariant();

        return view('livewire.shop.product-show', [
            'product' => $product,
            'variant' => $variant,
            'maxQuantity' => $this->ceiling($variant),
            'inBag' => app(Bag::class)->quantityOf($variant->sku),
            'wholesale' => WholesaleItem::for($variant),
            'similar' => $this->similar($product),
            'breadcrumb' => $this->breadcrumb($product),
        ])->layout('layouts::shop', [
            'title' => "{$product->name} by {$product->brand}",
            'description' => Str::limit((string) $product->short_description, 155),
            'active' => 'categories',
            'ogType' => 'product',
        ]);
    }

    /**
     * Home, the top category, the subcategory it sits in, then the product.
     *
     * @return array<string, string|null>
     */
    private function breadcrumb(Product $product): array
    {
        $items = ['Home' => route('shop.home')];
        $category = $product->category;
        $root = $category?->parent;

        if ($root !== null) {
            $items[$root->name] = route('shop.category', $root->slug);
        }

        if ($category !== null) {
            $items[$category->name] = route('shop.category', $category->slug);
        }

        $items[$product->name] = null;

        return $items;
    }

    /**
     * Other products from the same corner of the shop: its own subcategory
     * first, then the wider category.
     *
     * @return Collection<int, Product>
     */
    private function similar(Product $product, int $limit = 4): Collection
    {
        $category = $product->category;

        if ($category === null) {
            return new Collection;
        }

        $near = Product::query()
            ->active()
            ->forListing()
            ->inCategory($category)
            ->whereKeyNot($product->getKey())
            ->limit($limit)
            ->get();

        if ($near->count() >= $limit && $category->parent_id === null) {
            return $near;
        }

        $wider = Product::query()
            ->active()
            ->forListing()
            ->inCategory($category->parent ?? $category)
            ->whereKeyNot($product->getKey())
            ->whereNotIn('id', $near->modelKeys())
            ->limit($limit - $near->count())
            ->get();

        return $near->concat($wider)->take($limit)->values();
    }

    private ?Product $loaded = null;

    private function product(): Product
    {
        // Called from mount, render, the breadcrumb and every quantity check,
        // so it is resolved once per request rather than re-queried each time.
        return $this->loaded ??= Product::query()
            ->active()
            ->with([
                'category.parent',
                'variants' => fn ($variants) => $variants->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
                'variants.priceSlabs',
            ])
            ->where('slug', $this->slug)
            ->firstOr(fn () => abort(404));
    }

    private function selectedVariant(): ProductVariant
    {
        $product = $this->product();

        return $product->variants->firstWhere('sku', $this->sku)
            ?? $product->firstVariant()
            ?? abort(404);
    }

    /**
     * The most of this variant a customer may put in the bag.
     */
    private function ceiling(ProductVariant $variant): int
    {
        return CartItem::ceilingFor($variant);
    }

    private function clampedQuantity(): int
    {
        return max(1, min($this->quantity, $this->ceiling($this->selectedVariant())));
    }
}
