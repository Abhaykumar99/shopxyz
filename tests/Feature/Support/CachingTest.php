<?php

use App\Enums\BannerPlacement;
use App\Models\Banner;
use App\Models\Setting;
use App\Support\Home\HomeContent;
use App\Support\ShopSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('reads the settings table once and then serves them from cache', function () {
    Setting::create(['key' => 'shop.name', 'value' => 'Sharma Sweets']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Setting::map();
    Setting::map();
    Setting::map();

    $settingQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q): bool => str_contains($q['query'], 'settings'))
        ->count();

    expect($settingQueries)->toBe(1);
});

it('shows a changed setting on the next request, not after the cache expires', function () {
    Setting::create(['key' => 'shop.name', 'value' => 'Sharma Sweets']);

    expect(ShopSettings::saved()['shop.name'])->toBe('Sharma Sweets');

    Setting::where('key', 'shop.name')->first()->update(['value' => 'Sharma Sweets and Gifts']);

    expect(ShopSettings::saved()['shop.name'])->toBe('Sharma Sweets and Gifts');
});

it('clears the settings cache when one is deleted', function () {
    $setting = Setting::create(['key' => 'shop.tagline', 'value' => 'Fresh today']);

    expect(ShopSettings::saved())->toHaveKey('shop.tagline');

    $setting->delete();

    expect(ShopSettings::saved())->not->toHaveKey('shop.tagline');
});

it('reads each banner placement once per request', function () {
    Banner::factory()->placement(BannerPlacement::DesktopHero)->create();

    $home = new HomeContent;
    $home->desktopHero();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $home->desktopHero();
    $home->desktopHero();

    expect(collect(DB::getQueryLog())->filter(fn (array $q): bool => str_contains($q['query'], 'banners')))
        ->toBeEmpty();
});

it('shows a new banner at once', function () {
    expect((new HomeContent)->desktopHero())->toBeEmpty();

    Banner::factory()->placement(BannerPlacement::DesktopHero)->create([
        'title' => 'Diwali hampers are here',
    ]);

    expect((new HomeContent)->desktopHero()->pluck('title'))->toContain('Diwali hampers are here');
});

it('drops a banner from the homepage as soon as it is switched off', function () {
    $banner = Banner::factory()->placement(BannerPlacement::DesktopHero)->create();

    expect((new HomeContent)->desktopHero())->toHaveCount(1);

    $banner->update(['is_active' => false]);

    expect((new HomeContent)->desktopHero())->toBeEmpty();
});

it('keeps a scheduled banner off the homepage until it starts', function () {
    Banner::factory()->placement(BannerPlacement::DesktopHero)->scheduled()->create();

    expect((new HomeContent)->desktopHero())->toBeEmpty();
});

it('drops a banner once its run has finished', function () {
    Banner::factory()->placement(BannerPlacement::DesktopHero)->finished()->create();

    expect((new HomeContent)->desktopHero())->toBeEmpty();
});

it('never caches objects, which the cache config forbids deserialising', function () {
    expect(config('cache.serializable_classes'))->toBeFalse();

    Cache::put('probe.array', ['shop.name' => 'Sharma Sweets'], 60);

    expect(Cache::get('probe.array'))->toBe(['shop.name' => 'Sharma Sweets']);
});
