<?php

namespace App\Support\Demo;

/**
 * TEMPORARY (Phases 2–4): replaced by the Category model.
 */
final readonly class DemoCategory
{
    /**
     * @param  list<DemoCategory>  $children
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $description,
        public ?string $parentSlug = null,
        public array $children = [],
    ) {}

    /**
     * The top-level category used for colours and icons.
     */
    public function rootSlug(): string
    {
        return $this->parentSlug ?? $this->slug;
    }
}
