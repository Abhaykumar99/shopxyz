<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Inline SVG icons from resources/icons (Lucide, ISC licence).
 *
 * Returns the inner markup of an icon so <x-ui.icon> can wrap it in its own
 * <svg> element with the right size and accessibility attributes.
 */
final class Icon
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function inner(string $name): string
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        if (! preg_match('/^[a-z0-9-]+$/', $name) || ! is_file($path = self::path($name))) {
            throw new InvalidArgumentException("Unknown icon [{$name}]. Add it to resources/icons.");
        }

        $svg = (string) file_get_contents($path);
        $inner = preg_replace(['/^.*?<svg[^>]*>/s', '/<\/svg>\s*$/s'], '', $svg);

        return self::$cache[$name] = trim((string) $inner);
    }

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        $files = glob(resource_path('icons/*.svg')) ?: [];

        return array_map(fn (string $file): string => basename($file, '.svg'), $files);
    }

    private static function path(string $name): string
    {
        return resource_path("icons/{$name}.svg");
    }
}
