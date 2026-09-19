<?php

use App\Actions\Catalog\StoreProductImages;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * A real JPEG of a given size, so the resizing has something to work on.
 */
function uploadPhoto(int $width = 2000, int $height = 2000): string
{
    $file = UploadedFile::fake()->image('photo.jpg', $width, $height);
    $path = $file->store('products', 'public');

    return (string) $path;
}

it('stores a photo at two sizes and points the product at it', function () {
    $product = Product::factory()->create();

    app(StoreProductImages::class)->handle($product, [uploadPhoto()]);

    $image = $product->images()->sole();

    expect($image->path)->not->toBeNull()
        ->and($image->thumbnail_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($image->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($image->thumbnail_path))->toBeTrue();
});

it('shrinks a large photo rather than storing it whole', function () {
    $product = Product::factory()->create();

    app(StoreProductImages::class)->handle($product, [uploadPhoto(2400, 1800)]);

    $image = $product->images()->sole();
    $full = imagecreatefromstring((string) Storage::disk('public')->get($image->path));
    $thumb = imagecreatefromstring((string) Storage::disk('public')->get($image->thumbnail_path));

    expect(imagesx($full))->toBe(1200)
        ->and(imagesy($full))->toBe(900)
        ->and(imagesx($thumb))->toBe(600)
        // The card copy is meaningfully smaller than the full one.
        ->and(strlen((string) Storage::disk('public')->get($image->thumbnail_path)))
        ->toBeLessThan(strlen((string) Storage::disk('public')->get($image->path)));
});

it('never enlarges a photo that is already small', function () {
    $product = Product::factory()->create();

    app(StoreProductImages::class)->handle($product, [uploadPhoto(400, 400)]);

    $image = $product->images()->sole();

    expect(imagesx(imagecreatefromstring((string) Storage::disk('public')->get($image->path))))->toBe(400);
});

it('keeps the order the admin arranged', function () {
    $product = Product::factory()->create();

    app(StoreProductImages::class)->handle($product, [uploadPhoto(), uploadPhoto(), uploadPhoto()]);

    expect($product->images()->orderBy('sort_order')->pluck('sort_order')->all())->toBe([0, 1, 2]);
});

it('leaves photos alone on a later save rather than resizing them again', function () {
    $product = Product::factory()->create();
    $action = app(StoreProductImages::class);

    $action->handle($product, [uploadPhoto()]);
    $first = $product->images()->sole();

    $action->handle($product->fresh(), [$first->path]);

    expect($product->images()->sole()->getKey())->toBe($first->getKey())
        ->and($product->images()->sole()->thumbnail_path)->toBe($first->thumbnail_path);
});

it('removes a photo the admin took out, and its files', function () {
    $product = Product::factory()->create();
    $action = app(StoreProductImages::class);

    $action->handle($product, [uploadPhoto(), uploadPhoto()]);
    $keep = $product->images()->orderBy('sort_order')->first();
    $drop = $product->images()->orderByDesc('sort_order')->first();

    $action->handle($product->fresh(), [$keep->path]);

    expect($product->images()->count())->toBe(1)
        ->and(Storage::disk('public')->exists($drop->path))->toBeFalse()
        ->and(Storage::disk('public')->exists($drop->thumbnail_path))->toBeFalse();
});

it('ignores an upload that is not a readable image', function () {
    $product = Product::factory()->create();
    Storage::disk('public')->put('products/not-an-image.jpg', 'plain text');

    app(StoreProductImages::class)->handle($product, ['products/not-an-image.jpg']);

    expect($product->images()->count())->toBe(0);
});

it('falls back to the full image when a photo has no card copy', function () {
    $product = Product::factory()->create();
    app(StoreProductImages::class)->handle($product, [uploadPhoto()]);

    $image = $product->images()->sole();
    $image->update(['thumbnail_path' => null]);

    expect($image->fresh()->thumbnailUrl())->toBe($image->url());
});

it('opens the product editor with the photo field and a read-only stock count', function () {
    Storage::fake('public');
    $this->actingAs(App\Models\User::factory()->admin()->create());

    $product = Product::factory()->create();
    $product->variants()->create([
        'sku' => 'EDIT-1',
        'name' => 'Standard',
        'price_paise' => 19900,
        'stock_quantity' => 4,
        'low_stock_threshold' => 5,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->get('/admin/products/'.$product->getKey().'/edit')
        ->assertOk()
        ->assertSee('Product photos')
        ->assertSee('Options, price and stock')
        ->assertSee('Wholesale prices');
});

it('opens the editor of a product that already has photos and price bands', function () {
    Storage::fake('public');
    $this->actingAs(App\Models\User::factory()->admin()->create());

    $product = Product::factory()->create();
    $variant = $product->variants()->create([
        'sku' => 'BANDS-1',
        'name' => 'Box',
        'price_paise' => 99000,
        'stock_quantity' => 20,
        'low_stock_threshold' => 5,
        'is_active' => true,
        'sort_order' => 0,
    ]);
    $variant->priceSlabs()->create(['min_quantity' => 5, 'unit_price_paise' => 92000, 'is_active' => true]);
    app(StoreProductImages::class)->handle($product, [uploadPhoto()]);

    // The price-band list reads through the product, which had no such relation
    // until now — which is what made this page throw.
    $this->get('/admin/products/'.$product->getKey().'/edit')
        ->assertOk()
        ->assertSee('Wholesale prices');

    expect($product->priceSlabs()->count())->toBe(1);
});
