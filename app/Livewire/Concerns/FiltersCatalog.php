<?php

namespace App\Livewire\Concerns;

use App\Enums\ProductSort;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Sort, brand, price and stock filters shared by category and search pages.
 * Filter state lives in the URL so results can be shared and bookmarked.
 */
trait FiltersCatalog
{
    use WithPagination;

    public const PER_PAGE = 24;

    public const PRICE_RANGES = [
        'under-250' => ['Under ₹250', null, 24999],
        '250-500' => ['₹250 to ₹500', 25000, 50000],
        '500-1000' => ['₹500 to ₹1,000', 50001, 100000],
        'over-1000' => ['Over ₹1,000', 100001, null],
    ];

    #[Url(except: 'popular')]
    public string $sort = 'popular';

    /** @var list<string> */
    #[Url(except: [])]
    public array $brands = [];

    #[Url(except: '')]
    public string $price = '';

    #[Url(as: 'in_stock', except: false)]
    public bool $inStock = false;

    public function updated(string $property): void
    {
        if (in_array($property, ['sort', 'brands', 'price', 'inStock'], true) || str_starts_with($property, 'brands.')) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('brands', 'price', 'inStock');
        $this->resetPage();
    }

    public function removeBrand(string $brand): void
    {
        $this->brands = array_values(array_diff($this->brands, [$brand]));
        $this->resetPage();
    }

    public function activeFilterCount(): int
    {
        return count($this->brands) + ($this->price !== '' ? 1 : 0) + ($this->inStock ? 1 : 0);
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    protected function filteredProducts(?Category $category, ?string $search): LengthAwarePaginator
    {
        return $this->catalogQuery($category, $search)
            ->forListing()
            ->sorted(ProductSort::fromRequest($this->sort))
            ->paginate(self::PER_PAGE);
    }

    /**
     * The brands a customer can still filter by, given everything else they
     * have chosen. Built from the same query so an option never returns nothing.
     *
     * @return list<string>
     */
    protected function brandOptions(?Category $category, ?string $search): array
    {
        return Product::query()
            ->active()
            ->inCategory($category)
            ->search($search)
            ->whereNotNull('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function catalogQuery(?Category $category, ?string $search): \Illuminate\Database\Eloquent\Builder
    {
        [, $min, $max] = self::PRICE_RANGES[$this->price] ?? [null, null, null];

        return Product::query()
            ->active()
            ->inCategory($category)
            ->search($search)
            ->ofBrands(array_values(array_filter($this->brands, 'is_string')))
            ->pricedBetween($min, $max)
            ->when($this->inStock, fn ($query) => $query->inStock());
    }
}
