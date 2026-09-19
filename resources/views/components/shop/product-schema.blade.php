{{--
    Product structured data, so a search result can show the price and whether
    the item is in stock. Rendered in the body, which schema.org allows.
--}}
@props(['product', 'variant'])

@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->short_description,
        'sku' => $variant->sku,
        'brand' => ['@type' => 'Brand', 'name' => $product->brand],
        'category' => $product->rootCategorySlug(),
        'offers' => [
            '@type' => 'Offer',
            'url' => route('shop.product', $product->slug),
            'price' => number_format($variant->price_paise / 100, 2, '.', ''),
            'priceCurrency' => 'INR',
            'availability' => $variant->inStock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'seller' => ['@type' => 'Organization', 'name' => $shop->name],
        ],
    ];
@endphp

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
