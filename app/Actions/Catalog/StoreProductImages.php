<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Saves the photos an admin uploaded for a product.
 *
 * Every photo is stored twice: a full-size one for the product page and a
 * smaller one for cards. A category page shows twenty-four cards, so serving
 * the full image everywhere would cost a phone several megabytes. Resizing
 * happens here rather than in the browser, because the shop should decide what
 * it stores.
 */
final class StoreProductImages
{
    /** Wide enough for the product page on a large screen. */
    private const FULL_WIDTH = 1200;

    /** Twice the widest a card is drawn, so it stays sharp on a dense screen. */
    private const THUMB_WIDTH = 600;

    private const QUALITY = 82;

    /**
     * @param  list<string>  $paths  freshly uploaded files on the public disk, in the order the admin arranged them
     */
    public function handle(Product $product, array $paths): void
    {
        DB::transaction(function () use ($product, $paths): void {
            $kept = [];

            foreach ($paths as $position => $path) {
                $existing = $product->images()->where('path', $path)->first();

                if ($existing !== null) {
                    // Already stored and resized on an earlier save.
                    $existing->update(['sort_order' => $position]);
                    $kept[] = $existing->getKey();

                    continue;
                }

                $stored = $this->store($product, $path, $position);

                if ($stored !== null) {
                    $kept[] = $stored->getKey();
                }
            }

            // Photos the admin removed from the field go with their files.
            $product->images()->whereKeyNot($kept)->get()->each(function (ProductImage $image): void {
                $this->deleteFiles($image);
                $image->delete();
            });
        });
    }

    private function store(Product $product, string $path, int $position): ?ProductImage
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $full = $this->resized($path, self::FULL_WIDTH, 'full');
        $thumb = $this->resized($path, self::THUMB_WIDTH, 'card');

        if ($full === null) {
            // Not an image we can read; leave the upload alone rather than
            // recording a row that points at something unusable.
            return null;
        }

        // The original upload is replaced by its resized versions.
        if ($full !== $path) {
            $disk->delete($path);
        }

        return $product->images()->create([
            'path' => $full,
            'thumbnail_path' => $thumb,
            'alt' => $product->name,
            'sort_order' => $position,
        ]);
    }

    /**
     * Writes a copy no wider than `$width`, keeping the aspect ratio, and
     * returns its path. A picture already narrower than that is copied as is.
     */
    private function resized(string $path, int $width, string $suffix): ?string
    {
        $disk = Storage::disk('public');
        $source = @imagecreatefromstring((string) $disk->get($path));

        if ($source === false) {
            return null;
        }

        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        $targetWidth = min($width, $originalWidth);
        $targetHeight = (int) round($originalHeight * ($targetWidth / $originalWidth));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);

        ob_start();
        imagejpeg($canvas, null, self::QUALITY);
        $encoded = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        $target = 'products/'.Str::uuid()->toString().'-'.$suffix.'.jpg';
        $disk->put($target, $encoded);

        return $target;
    }

    private function deleteFiles(ProductImage $image): void
    {
        $disk = Storage::disk('public');

        foreach (array_filter([$image->path, $image->thumbnail_path]) as $path) {
            $disk->delete($path);
        }
    }
}
