<?php

namespace App\Support\Demo;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * TEMPORARY sample catalogue for the UI-first phases (2–4).
 *
 * Replaced by Eloquent queries in Phase 5. Brand names are fictional and
 * amounts are integer paise (ADR-005).
 */
final class DemoCatalog
{
    public const SORTS = [
        'popular' => 'Most popular',
        'newest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'discount' => 'Biggest discount',
    ];

    /** @var list<DemoCategory>|null */
    private static ?array $categories = null;

    /** @var list<DemoProduct>|null */
    private static ?array $products = null;

    /**
     * Top-level categories with their subcategories.
     *
     * @return list<DemoCategory>
     */
    public static function categories(): array
    {
        return self::$categories ??= [
            new DemoCategory('cosmetics', 'Cosmetics', 'Lipsticks, kajal, skincare and fragrance from brands we trust.', children: [
                new DemoCategory('lips', 'Lips', 'Lipsticks, tints and balms.', 'cosmetics'),
                new DemoCategory('eyes', 'Eyes', 'Kajal, liner and mascara.', 'cosmetics'),
                new DemoCategory('face', 'Face', 'Compact, foundation and blush.', 'cosmetics'),
                new DemoCategory('skincare', 'Skincare', 'Face wash, gels and creams.', 'cosmetics'),
                new DemoCategory('fragrance', 'Fragrance', 'Attars and body mists.', 'cosmetics'),
            ]),
            new DemoCategory('confectionery', 'Confectionery', 'Fresh mithai, chocolates, dry fruits and cookies.', children: [
                new DemoCategory('mithai', 'Mithai', 'Made fresh every morning.', 'confectionery'),
                new DemoCategory('chocolates', 'Chocolates', 'Truffles, bars and assorted boxes.', 'confectionery'),
                new DemoCategory('dry-fruits', 'Dry fruits', 'Premium nuts and dried fruit.', 'confectionery'),
                new DemoCategory('cookies', 'Cookies and cakes', 'Baked in small batches.', 'confectionery'),
            ]),
            new DemoCategory('gifts', 'Gifts', 'Hampers, candles and keepsakes for every occasion.', children: [
                new DemoCategory('hampers', 'Hampers', 'Ready-to-gift boxes.', 'gifts'),
                new DemoCategory('candles-decor', 'Candles and decor', 'Diyas, candles and home accents.', 'gifts'),
                new DemoCategory('personalised', 'Personalised', 'Names, photos and messages.', 'gifts'),
                new DemoCategory('gift-sets', 'Beauty gift sets', 'Pampering sets, ready to give.', 'gifts'),
            ]),
        ];
    }

    public static function category(string $slug): ?DemoCategory
    {
        foreach (self::categories() as $category) {
            if ($category->slug === $slug) {
                return $category;
            }
            foreach ($category->children as $child) {
                if ($child->slug === $slug) {
                    return $child;
                }
            }
        }

        return null;
    }

    public static function productCount(DemoCategory $category): int
    {
        return self::query(category: $category->slug)->count();
    }

    /**
     * @return list<DemoProduct>
     */
    public static function products(): array
    {
        return self::$products ??= self::build();
    }

    public static function product(string $slug): ?DemoProduct
    {
        foreach (self::products() as $product) {
            if ($product->slug === $slug) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Finds the product and variant for a SKU.
     *
     * @return array{0: DemoProduct, 1: DemoVariant}|null
     */
    public static function findSku(string $sku): ?array
    {
        foreach (self::products() as $product) {
            if ($variant = $product->variant($sku)) {
                return [$product, $variant];
            }
        }

        return null;
    }

    /**
     * Filter and sort the catalogue.
     *
     * @param  list<string>  $brands
     * @return Collection<int, DemoProduct>
     */
    public static function query(
        ?string $category = null,
        ?string $search = null,
        array $brands = [],
        ?int $minPaise = null,
        ?int $maxPaise = null,
        bool $inStockOnly = false,
        string $sort = 'popular',
    ): Collection {
        $terms = array_filter(explode(' ', Str::lower(trim((string) $search))));

        $results = collect(self::products())
            ->when($category, fn (Collection $items) => $items->filter(
                fn (DemoProduct $product): bool => $product->category === $category || $product->subcategory === $category,
            ))
            ->when($terms !== [], fn (Collection $items) => $items->filter(function (DemoProduct $product) use ($terms): bool {
                $subcategory = self::category($product->subcategory);
                $haystack = Str::lower(implode(' ', [$product->name, $product->brand, $product->category, $subcategory?->name, $product->summary]));

                return collect($terms)->every(fn (string $term): bool => str_contains($haystack, $term));
            }))
            ->when($brands !== [], fn (Collection $items) => $items->filter(fn (DemoProduct $product): bool => in_array($product->brand, $brands, true)))
            ->when($minPaise !== null, fn (Collection $items) => $items->filter(fn (DemoProduct $product): bool => $product->lowestPrice() >= $minPaise))
            ->when($maxPaise !== null, fn (Collection $items) => $items->filter(fn (DemoProduct $product): bool => $product->lowestPrice() <= $maxPaise))
            ->when($inStockOnly, fn (Collection $items) => $items->filter(fn (DemoProduct $product): bool => $product->inStock()));

        $sorted = match ($sort) {
            'newest' => $results->sortBy('addedDaysAgo'),
            'price_asc' => $results->sortBy(fn (DemoProduct $product): int => $product->lowestPrice()),
            'price_desc' => $results->sortByDesc(fn (DemoProduct $product): int => $product->lowestPrice()),
            'discount' => $results->sortByDesc(fn (DemoProduct $product): int => $product->bestDiscount()),
            default => $results->sortBy('rank'),
        };

        return $sorted->values();
    }

    /**
     * Brands available in a category (or the whole catalogue), for the filter.
     *
     * @return list<string>
     */
    public static function brands(?string $category = null, ?string $search = null): array
    {
        return self::query(category: $category, search: $search)->pluck('brand')->unique()->sort()->values()->all();
    }

    /**
     * @return Collection<int, DemoProduct>
     */
    public static function tagged(string $tag, int $limit = 8): Collection
    {
        return self::query()->filter(fn (DemoProduct $product): bool => $product->hasTag($tag))->take($limit)->values();
    }

    /**
     * @return Collection<int, DemoProduct>
     */
    public static function offers(int $limit = 8): Collection
    {
        return self::query(inStockOnly: true, sort: 'discount')
            ->filter(fn (DemoProduct $product): bool => $product->bestDiscount() >= 15)
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, DemoProduct>
     */
    public static function similar(DemoProduct $product, int $limit = 4): Collection
    {
        return self::query(category: $product->subcategory)
            ->concat(self::query(category: $product->category))
            ->reject(fn (DemoProduct $other): bool => $other->slug === $product->slug)
            ->unique('slug')
            ->take($limit)
            ->values();
    }

    /**
     * @return list<DemoProduct>
     */
    private static function build(): array
    {
        $shades = fn (string $prefix, array $shades, int $paise, ?int $mrp, array $stock = []): array => array_map(
            fn (string $name, string $hex, int $index): DemoVariant => new DemoVariant("{$prefix}-".($index + 1), $name, $paise, $mrp, $stock[$index] ?? 15, $hex),
            array_keys($shades),
            array_values($shades),
            array_keys(array_values($shades)),
        );

        $sizes = fn (string $prefix, array $sizes): array => array_map(
            fn (array $size, int $index): DemoVariant => new DemoVariant("{$prefix}-".($index + 1), $size[0], $size[1], $size[2] ?? null, $size[3] ?? 20),
            $sizes,
            array_keys($sizes),
        );

        return [
            // Cosmetics
            new DemoProduct('velvet-matte-lipstick', 'Velvet matte lipstick', 'Blush & Bloom', 'cosmetics', 'lips',
                'Weightless matte colour that lasts through chai and chats.',
                'A creamy matte formula enriched with vitamin E. Glides on in one stroke, stays put for up to 8 hours and never feels dry.',
                $shades('BB-LIP', ['Rosewood' => '#8E3B46', 'Mulberry' => '#6D1F3B', 'Coral crush' => '#D9574A', 'Nude dusk' => '#B07A6A', 'Brick red' => '#8B2A1E'], 34900, 49900, [15, 12, 0, 8, 2]),
                ['Up to 8 hours wear', 'Vitamin E enriched', 'Cruelty free'], ['bestseller', 'featured'], rank: 1, addedDaysAgo: 40, variantLabel: 'Shade'),
            new DemoProduct('tinted-lip-balm', 'Tinted lip balm with SPF 15', 'Green Leaf', 'cosmetics', 'lips',
                'A sheer wash of colour with everyday sun protection.',
                'Shea butter and cocoa butter keep lips soft, while a hint of tint adds a healthy flush. SPF 15.',
                $shades('GL-BALM', ['Cherry' => '#B23A48', 'Peach' => '#E08E6D', 'Berry' => '#7D2E4B'], 19900, 24900),
                ['SPF 15', 'Shea and cocoa butter'], [], rank: 12, addedDaysAgo: 12, variantLabel: 'Shade'),
            new DemoProduct('liquid-lipstick-duo', 'Long-stay liquid lipstick duo', 'Rang Studio', 'cosmetics', 'lips',
                'Two transfer-proof shades in one gift-ready box.',
                'A matching pair of liquid lipsticks with a soft-focus finish. Transfer-proof once set.',
                $shades('RS-DUO', ['Wine and rose' => '#5E1A2E', 'Berry and nude' => '#7E3552'], 59900, 79800),
                ['Transfer-proof', 'Gift-ready box'], ['festive'], rank: 18, addedDaysAgo: 5, variantLabel: 'Pair'),
            new DemoProduct('smudge-proof-kajal', 'Smudge-proof kajal', 'Blush & Bloom', 'cosmetics', 'eyes',
                'Intense black that stays sharp all day.',
                'One swipe of deep black with camphor and almond oil. Waterproof and ophthalmologically tested.',
                $sizes('BB-KAJ', [['Single, 0.35 g', 14900, 19900, 40], ['Pack of 2', 27900, 39800, 25]]),
                ['Waterproof', 'Ophthalmologically tested'], ['bestseller'], rank: 2, addedDaysAgo: 60, variantLabel: 'Pack'),
            new DemoProduct('volume-mascara', 'Lash volume mascara', 'Rang Studio', 'cosmetics', 'eyes',
                'Builds bold lashes without clumps.',
                'A curved brush lifts and separates lashes while the formula adds buildable volume.',
                $sizes('RS-MASC', [['8 ml', 44900, 54900, 10]]),
                ['Buildable volume', 'Clump-free brush'], [], rank: 20, addedDaysAgo: 22),
            new DemoProduct('gel-eyeliner', 'Gel eyeliner pencil', 'Noor Beauty', 'cosmetics', 'eyes',
                'Creamy glide, sets in 30 seconds.',
                'A gel liner in pencil form for sharp wings and smoky looks alike.',
                $shades('NB-GEL', ['Jet black' => '#141414', 'Espresso' => '#3B2A22', 'Navy' => '#1F2A4D'], 29900, null),
                ['Sets in 30 seconds'], [], rank: 25, addedDaysAgo: 3, variantLabel: 'Shade'),
            new DemoProduct('matte-compact', 'Oil-control matte compact', 'Noor Beauty', 'cosmetics', 'face',
                'Soaks up shine in Patna summers.',
                'A lightweight pressed powder with SPF 20 that controls oil for up to 6 hours.',
                $shades('NB-CMP', ['Ivory' => '#EBCFB2', 'Natural beige' => '#D6AE8B', 'Warm honey' => '#B98560'], 39900, 45000, [10, 18, 6]),
                ['SPF 20', 'Oil control up to 6 hours'], [], rank: 9, addedDaysAgo: 30, variantLabel: 'Shade'),
            new DemoProduct('cream-blush-stick', 'Cream blush stick', 'Rang Studio', 'cosmetics', 'face',
                'Dab, blend, glow.',
                'A buildable cream blush that melts into skin for a natural flush.',
                $shades('RS-BLSH', ['Petal' => '#E7929B', 'Apricot' => '#E89A72'], 34900, null, [0, 0]),
                ['Buildable colour'], [], rank: 30, addedDaysAgo: 15, variantLabel: 'Shade'),
            new DemoProduct('aloe-cucumber-face-gel', 'Aloe and cucumber face gel', 'Green Leaf', 'cosmetics', 'skincare',
                'Instant cool-down for tired skin.',
                'Soothing aloe vera with cucumber extract hydrates without feeling sticky. Suits all skin types.',
                $sizes('GL-GEL', [['100 ml', 19950, 22000, 30], ['250 ml', 39900, 45000, 12]]),
                ['Non-sticky', 'All skin types'], [], rank: 6, addedDaysAgo: 45),
            new DemoProduct('vitamin-c-serum', 'Vitamin C brightening serum', 'Green Leaf', 'cosmetics', 'skincare',
                'Brighter, more even skin in four weeks.',
                'A stable 10% vitamin C serum with hyaluronic acid for a daily glow.',
                $sizes('GL-VITC', [['30 ml', 54900, 69900, 3]]),
                ['10% vitamin C', 'With hyaluronic acid'], ['featured'], rank: 7, addedDaysAgo: 8),
            new DemoProduct('rose-water-toner', 'Pure rose water toner', 'Noor Beauty', 'cosmetics', 'skincare',
                'Steam-distilled from Kannauj roses.',
                'Refreshes and tones. Use after cleansing or as a mid-day mist.',
                $sizes('NB-ROSE', [['100 ml', 14900, null, 50], ['200 ml', 24900, null, 35]]),
                ['Steam distilled', 'Alcohol free'], [], rank: 14, addedDaysAgo: 90),
            new DemoProduct('oud-attar', 'Oud and amber attar', 'Itr Mahal', 'cosmetics', 'fragrance',
                'A warm, long-lasting alcohol-free perfume oil.',
                'Rich oud softened with amber and a touch of rose. A few dabs last all day.',
                $sizes('IM-OUD', [['6 ml roll-on', 49900, 59900, 14], ['12 ml', 89900, 109900, 6]]),
                ['Alcohol free', 'Long lasting'], ['festive'], rank: 16, addedDaysAgo: 18),
            new DemoProduct('jasmine-body-mist', 'Jasmine body mist', 'Itr Mahal', 'cosmetics', 'fragrance',
                'Fresh mogra in a light, everyday mist.',
                'A light fragrance mist with the scent of fresh jasmine garlands.',
                $sizes('IM-MIST', [['150 ml', 29900, 34900, 22]]),
                ['Everyday fragrance'], [], rank: 22, addedDaysAgo: 35),

            // Confectionery
            new DemoProduct('kaju-katli', 'Kaju katli with silver leaf', 'Mithai Ghar', 'confectionery', 'mithai',
                'Melt-in-the-mouth cashew fudge, made fresh every morning.',
                'Our signature kaju katli, made with premium cashews and a light touch of sugar, finished with edible silver leaf. Best within 7 days.',
                $sizes('MG-KK', [['250 g', 28000, null, 30], ['500 g box', 52000, null, 25], ['1 kg box', 99000, 104000, 10]]),
                ['Made fresh daily', 'Best within 7 days', 'Pure ghee'], ['bestseller', 'festive', 'featured'], rank: 3, addedDaysAgo: 120, variantLabel: 'Weight'),
            new DemoProduct('motichoor-ladoo', 'Motichoor ladoo', 'Mithai Ghar', 'confectionery', 'mithai',
                'Tiny boondi pearls in pure ghee, flavoured with cardamom.',
                'Soft, fragrant ladoos made with fine boondi, pure ghee, cardamom and saffron.',
                $sizes('MG-ML', [['250 g', 18000, null, 40], ['500 g', 34000, null, 30]]),
                ['Pure ghee', 'Saffron and cardamom'], ['bestseller'], rank: 4, addedDaysAgo: 110, variantLabel: 'Weight'),
            new DemoProduct('rose-gulkand-ladoo', 'Rose gulkand ladoo', 'Mithai Ghar', 'confectionery', 'mithai',
                'Coconut ladoo with a heart of rose gulkand.',
                'A festive favourite: soft coconut ladoo filled with sweet rose petal preserve.',
                $sizes('MG-RGL', [['250 g', 28000, null, 18]]),
                ['Rose petal filling'], ['festive'], rank: 13, addedDaysAgo: 10, variantLabel: 'Weight'),
            new DemoProduct('assorted-mithai-box', 'Assorted mithai box', 'Mithai Ghar', 'confectionery', 'mithai',
                'Six favourites in one festive box.',
                'Kaju katli, motichoor ladoo, milk cake, besan barfi, pista roll and gulkand ladoo.',
                $sizes('MG-ASST', [['500 g', 49900, 55000, 20], ['1 kg', 94900, 110000, 8]]),
                ['Six varieties', 'Gift-ready box'], ['festive', 'featured'], rank: 5, addedDaysAgo: 7, variantLabel: 'Weight'),
            new DemoProduct('dark-chocolate-truffles', 'Dark chocolate truffles', 'Cocoa Lane', 'confectionery', 'chocolates',
                'Hand-rolled 70% dark truffles.',
                'Silky ganache centres rolled in cocoa, with sea salt, orange and coffee flavours.',
                $sizes('CL-TRF', [['9 pieces', 44900, 49900, 15], ['16 pieces', 69900, 79900, 12]]),
                ['70% dark chocolate', 'Hand rolled'], ['featured', 'festive'], rank: 8, addedDaysAgo: 14, variantLabel: 'Box'),
            new DemoProduct('milk-chocolate-almonds', 'Milk chocolate almonds', 'Cocoa Lane', 'confectionery', 'chocolates',
                'Roasted almonds in creamy milk chocolate.',
                'Crunchy California almonds coated in smooth milk chocolate.',
                $sizes('CL-ALM', [['150 g', 29900, 34900, 25]]),
                ['Roasted almonds'], [], rank: 17, addedDaysAgo: 50),
            new DemoProduct('artisan-chocolate-bar', 'Artisan chocolate bar trio', 'Cocoa Lane', 'confectionery', 'chocolates',
                'Three single-origin bars to taste side by side.',
                'Bars from Kerala, Andhra Pradesh and Karnataka cacao, 55% to 72% cocoa.',
                $sizes('CL-TRIO', [['3 x 50 g', 59900, null, 0]]),
                ['Single-origin Indian cacao'], [], rank: 28, addedDaysAgo: 2),
            new DemoProduct('premium-dry-fruit-box', 'Premium dry fruit box', 'Utsav Gifting', 'confectionery', 'dry-fruits',
                'Almonds, cashews, pistachios and raisins in a keepsake box.',
                'Handpicked premium dry fruits in four compartments of a reusable wooden box.',
                $sizes('UG-DRY', [['400 g', 89900, 109900, 12], ['800 g', 164900, 199900, 5]]),
                ['Reusable wooden box', 'Handpicked'], ['festive', 'featured'], rank: 10, addedDaysAgo: 20, variantLabel: 'Weight'),
            new DemoProduct('salted-pistachios', 'Roasted salted pistachios', 'Utsav Gifting', 'confectionery', 'dry-fruits',
                'Crunchy, lightly salted, easy to open.',
                'Large Iranian pistachios, roasted and lightly salted.',
                $sizes('UG-PIS', [['200 g', 39900, 44900, 30]]),
                ['Lightly salted'], [], rank: 24, addedDaysAgo: 65, variantLabel: 'Weight'),
            new DemoProduct('butter-cookies-tin', 'Butter cookies tin', 'Bake House', 'confectionery', 'cookies',
                'Classic Danish-style butter cookies.',
                'Crisp, buttery cookies in five shapes, packed in a gift tin.',
                $sizes('BH-TIN', [['400 g tin', 34900, 39900, 20]]),
                ['Gift tin'], ['bestseller'], rank: 11, addedDaysAgo: 80),
            new DemoProduct('plum-cake', 'Rich plum cake', 'Bake House', 'confectionery', 'cookies',
                'Soaked fruit, warm spices, baked slow.',
                'A moist plum cake loaded with fruit and nuts. Eggless option available.',
                $sizes('BH-PLUM', [['500 g', 44900, null, 10], ['500 g eggless', 47900, null, 1]]),
                ['Eggless option'], [], rank: 27, addedDaysAgo: 1, variantLabel: 'Type'),

            // Gifts
            new DemoProduct('festive-dry-fruit-hamper', 'Festive hamper with brass diya', 'Utsav Gifting', 'gifts', 'hampers',
                'Dry fruits, mithai and a brass diya, ready to gift.',
                'A wicker hamper with premium dry fruits, a box of kaju katli, a brass diya and a handwritten card.',
                $sizes('UG-HMP', [['Medium', 149900, 179900, 8], ['Large', 249900, 299900, 4]]),
                ['Handwritten card included', 'Gift wrapped'], ['festive', 'featured', 'bestseller'], rank: 1, addedDaysAgo: 6),
            new DemoProduct('chocolate-lovers-hamper', "Chocolate lover's hamper", 'Cocoa Lane', 'gifts', 'hampers',
                'Truffles, bars and cookies in one box.',
                'A mix of our best chocolates and butter cookies in a velvet-lined box.',
                $sizes('CL-HMP', [['Standard', 129900, 149900, 6]]),
                ['Velvet-lined box'], ['festive'], rank: 15, addedDaysAgo: 9),
            new DemoProduct('scented-candle-trio', 'Scented candle trio', 'Diya House', 'gifts', 'candles-decor',
                'Sandalwood, jasmine and vetiver soy candles.',
                'Three hand-poured soy wax candles in reusable glass jars. About 25 hours each.',
                $sizes('DH-CND', [['Set of 3', 89900, null, 16]]),
                ['Soy wax', 'About 25 hours each'], ['featured', 'festive'], rank: 3, addedDaysAgo: 25),
            new DemoProduct('brass-diya-set', 'Brass diya set', 'Diya House', 'gifts', 'candles-decor',
                'Handcrafted brass diyas that last for years.',
                'A set of four polished brass diyas made by artisans in Moradabad.',
                $sizes('DH-DIYA', [['Set of 4', 69900, 84900, 12], ['Set of 8', 129900, 159900, 3]]),
                ['Handcrafted in Moradabad'], ['festive'], rank: 8, addedDaysAgo: 12, variantLabel: 'Set'),
            new DemoProduct('fairy-light-jar', 'Fairy light jar', 'Diya House', 'gifts', 'candles-decor',
                'A warm glow for any shelf.',
                'A glass jar with 30 warm-white LED lights. Runs on AA batteries.',
                $sizes('DH-JAR', [['Standard', 39900, 49900, 0]]),
                ['Battery operated'], [], rank: 29, addedDaysAgo: 40),
            new DemoProduct('personalised-photo-mug', 'Personalised photo mug', 'Keepsake Co.', 'gifts', 'personalised',
                'Your photo and message on a ceramic mug.',
                'A 330 ml ceramic mug printed with your photo and message. Ready in 2 days.',
                $sizes('KC-MUG', [['White', 39900, null, 50], ['Magic (reveals when hot)', 54900, null, 30]]),
                ['Ready in 2 days', 'Dishwasher safe'], ['bestseller'], rank: 6, addedDaysAgo: 70, variantLabel: 'Style'),
            new DemoProduct('engraved-wooden-frame', 'Engraved wooden photo frame', 'Keepsake Co.', 'gifts', 'personalised',
                'A name and date laser-engraved on sheesham wood.',
                'Holds a 5x7 inch photo. Engraving of up to 30 characters included.',
                $sizes('KC-FRM', [['5x7 inch', 64900, 74900, 9]]),
                ['Sheesham wood', 'Up to 30 characters'], [], rank: 19, addedDaysAgo: 28),
            new DemoProduct('pamper-gift-set', 'Pamper me beauty gift set', 'Blush & Bloom', 'gifts', 'gift-sets',
                'Lipstick, kajal, face gel and mist in a keepsake box.',
                'Our favourite everyday beauty picks, beautifully boxed with a ribbon.',
                $sizes('BB-SET', [['Classic', 99900, 132600, 7]]),
                ['Four full-size products', 'Ribbon-tied box'], ['festive', 'featured'], rank: 2, addedDaysAgo: 4),
            new DemoProduct('mini-attar-collection', 'Mini attar collection', 'Itr Mahal', 'gifts', 'gift-sets',
                'Five 3 ml attars to discover a favourite.',
                'Rose, oud, jasmine, sandalwood and musk in a velvet pouch.',
                $sizes('IM-MINI', [['5 x 3 ml', 79900, 99900, 2]]),
                ['Five fragrances', 'Velvet pouch'], ['festive'], rank: 21, addedDaysAgo: 16),
        ];
    }
}
