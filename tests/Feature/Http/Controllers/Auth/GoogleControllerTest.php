<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    Config::set('services.google', [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect' => 'http://localhost/auth/google/callback',
    ]);
});

/**
 * Stands in for the account Google hands back, so no test ever leaves the machine.
 */
function googleAccount(array $overrides = []): SocialiteUser
{
    $raw = [
        'sub' => '110000000000000000001',
        'name' => 'Priya Sharma',
        'email' => 'priya.sharma@example.com',
        'email_verified' => true,
        'picture' => 'https://lh3.googleusercontent.com/photo',
        ...$overrides,
    ];

    $user = new SocialiteUser;
    $user->setRaw($raw)->map([
        'id' => $raw['sub'],
        'name' => $raw['name'],
        'email' => $raw['email'],
        'avatar' => $raw['picture'],
    ]);

    return $user;
}

function googleReturns(SocialiteUser $account): void
{
    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($account);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('sends the visitor to Google', function () {
    $this->get('/auth/google/redirect')
        ->assertRedirectContains('accounts.google.com');
});

it('creates a customer on the first sign-in', function () {
    googleReturns(googleAccount());

    $this->get('/auth/google/callback')->assertRedirect(route('account.profile'));

    $customer = User::sole();

    expect($customer->google_id)->toBe('110000000000000000001')
        ->and($customer->email)->toBe('priya.sharma@example.com')
        ->and($customer->name)->toBe('Priya Sharma')
        ->and($customer->role)->toBe(UserRole::Customer)
        ->and($customer->password)->toBeNull()
        ->and($customer->phone)->toBeNull()
        ->and($customer->email_verified_at)->not->toBeNull()
        ->and($customer->last_login_at)->not->toBeNull()
        ->and(auth()->id())->toBe($customer->id);
});

it('matches a returning customer on their Google id, not their email', function () {
    $existing = User::factory()->googleCustomer()->create([
        'google_id' => '110000000000000000001',
        'email' => 'old.address@example.com',
        'name' => 'Priya S',
    ]);

    googleReturns(googleAccount());

    $this->get('/auth/google/callback')->assertRedirect(route('account.profile'));

    expect(User::count())->toBe(1)
        ->and($existing->fresh()->name)->toBe('Priya Sharma')
        ->and(auth()->id())->toBe($existing->id);
});

it('links a customer the shop already knew by email', function () {
    $existing = User::factory()->googleCustomer()->create([
        'google_id' => null,
        'email' => 'priya.sharma@example.com',
    ]);

    googleReturns(googleAccount());

    $this->get('/auth/google/callback')->assertRedirect(route('account.profile'));

    expect(User::count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('110000000000000000001');
});

it('refuses an unverified Google email', function () {
    googleReturns(googleAccount(['email_verified' => false]));

    $this->get('/auth/google/callback')
        ->assertRedirect(route('auth.login'))
        ->assertSessionHas('toast');

    expect(User::count())->toBe(0)
        ->and(auth()->check())->toBeFalse();
});

it('never signs a staff account in through Google', function (string $state) {
    $staff = User::factory()->{$state}()->create(['email' => 'priya.sharma@example.com']);

    googleReturns(googleAccount());

    $this->get('/auth/google/callback')->assertRedirect(route('auth.login'));

    expect(auth()->check())->toBeFalse()
        ->and($staff->fresh()->google_id)->toBeNull();
})->with(['admin', 'deliveryPartner']);

it('refuses an account the shop has closed or switched off', function (string $change) {
    $customer = User::factory()->googleCustomer()->create([
        'google_id' => '110000000000000000001',
        'email' => 'priya.sharma@example.com',
    ]);

    $change === 'deleted' ? $customer->delete() : $customer->forceFill(['is_active' => false])->save();

    googleReturns(googleAccount());

    $this->get('/auth/google/callback')->assertRedirect(route('auth.login'));

    expect(auth()->check())->toBeFalse();
})->with(['deleted', 'inactive']);

it('returns the customer to where they were going', function () {
    googleReturns(googleAccount());

    session()->put('url.intended', route('checkout.show'));

    $this->get('/auth/google/callback')->assertRedirect(route('checkout.show'));
});

it('takes a cancelled sign-in back to the sign-in page', function () {
    $this->get('/auth/google/callback?error=access_denied')
        ->assertRedirect(route('auth.login'))
        ->assertSessionHas('toast');

    expect(User::count())->toBe(0);
});

it('survives Google failing without leaking why', function () {
    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andThrow(new RuntimeException('invalid state'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')
        ->assertRedirect(route('auth.login'))
        ->assertSessionHas('toast');

    expect(auth()->check())->toBeFalse();
});

it('hides the routes when the shop has no Google client', function () {
    Config::set('services.google', ['client_id' => null, 'client_secret' => null, 'redirect' => null]);

    $this->get('/auth/google/redirect')->assertNotFound();
    $this->get('/auth/google/callback')->assertNotFound();
    $this->get('/login')->assertOk()->assertDontSee('auth/google/redirect');
});

it('throttles the callback', function () {
    googleReturns(googleAccount(['email_verified' => false]));

    foreach (range(1, 10) as $attempt) {
        $this->get('/auth/google/callback');
    }

    $this->get('/auth/google/callback')->assertStatus(429);
});
