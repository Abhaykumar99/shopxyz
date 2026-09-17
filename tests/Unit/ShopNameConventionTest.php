<?php

/*
 * ADR-013: the shop name is a setting. The temporary demo name may only
 * appear in config/shop.php; everything else must read ShopSettings.
 */

it('keeps the demo shop name out of application code', function () {
    $root = dirname(__DIR__, 2);
    preg_match("/env\('SHOP_DEMO_NAME',\s*'([^']+)'\)/", (string) file_get_contents("{$root}/config/shop.php"), $match);
    $demoName = $match[1] ?? null;

    $allowed = realpath("{$root}/config/shop.php");
    $offenders = [];
    foreach (['app', 'resources', 'routes', 'database', 'config'] as $directory) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}", FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $file->getPathname();
            if ($file->isFile() && realpath($path) !== $allowed && str_contains((string) file_get_contents($path), (string) $demoName)) {
                $offenders[] = substr($path, strlen($root) + 1);
            }
        }
    }

    expect($demoName)->not->toBeEmpty()
        ->and($offenders)->toBe([]);
});
