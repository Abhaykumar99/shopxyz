<?php

namespace App\Livewire\Concerns;

use App\Support\Demo\DemoCatalog;
use App\Support\Demo\DemoProduct;
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
     * @return LengthAwarePaginator<int, DemoProduct>
     */
    protected function filteredProducts(?string $category, ?string $search): LengthAwarePaginator
    {
        $sort = array_key_exists($this->sort, DemoCatalog::SORTS) ? $this->sort : 'popular';
        [, $min, $max] = self::PRICE_RANGES[$this->price] ?? [null, null, null];
        $brands = array_values(array_filter($this->brands, 'is_string'));

        $results = DemoCatalog::query(
            category: $category,
            search: $search,
            brands: $brands,
            minPaise: $min,
            maxPaise: $max,
            inStockOnly: $this->inStock,
            sort: $sort,
        );

        $page = max(1, $this->getPage());

        return new LengthAwarePaginator(
            $results->forPage($page, self::PER_PAGE)->values(),
            $results->count(),
            self::PER_PAGE,
            $page,
        );
    }
}
