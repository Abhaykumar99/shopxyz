<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as GoogleUser;

/**
 * Turns a Google account into the customer behind it (ADR-004).
 *
 * A returning customer is matched on `google_id` first, because that is the only
 * identifier Google promises is stable — someone can change the Gmail address on
 * the same account. Falling back to the email address is safe only because Google
 * says it has verified it, which is checked before either lookup.
 *
 * A staff account is never linked this way: admins and delivery partners have
 * their own sign-in, and reaching them through a personal Google account would
 * hand the panel to whoever controls that mailbox.
 */
final class FindOrCreateGoogleCustomer
{
    public function handle(GoogleUser $account): User
    {
        $googleId = (string) $account->getId();
        $email = mb_strtolower(trim((string) $account->getEmail()));

        if ($googleId === '' || $email === '') {
            throw GoogleSignInDenied::incomplete();
        }

        if (! $this->emailIsVerified($account)) {
            throw GoogleSignInDenied::unverifiedEmail();
        }

        return DB::transaction(function () use ($account, $googleId, $email): User {
            $existing = $this->match($googleId, $email);

            if ($existing === null) {
                return $this->create($account, $googleId, $email);
            }

            if ($existing->role !== UserRole::Customer) {
                throw GoogleSignInDenied::staffAccount();
            }

            if ($existing->trashed() || ! $existing->is_active) {
                throw GoogleSignInDenied::closedAccount();
            }

            return $this->refresh($existing, $account, $googleId);
        });
    }

    /**
     * The Google id wins over the email, so a customer who changed their Gmail
     * address keeps the same account instead of being given a second one.
     * Soft-deleted rows hold on to their unique email and `google_id`, so they
     * have to be found here rather than collide on insert.
     */
    private function match(string $googleId, string $email): ?User
    {
        return User::withTrashed()->firstWhere('google_id', $googleId)
            ?? User::withTrashed()->firstWhere('email', $email);
    }

    /**
     * Google reports this in the raw profile, not on Socialite's own contract.
     */
    private function emailIsVerified(GoogleUser $account): bool
    {
        $raw = is_array($account->user ?? null) ? $account->user : [];

        return (bool) (Arr::get($raw, 'email_verified') ?? Arr::get($raw, 'verified_email') ?? false);
    }

    /**
     * Written with `forceCreate` because `email_verified_at` is deliberately not
     * mass-assignable. Every value here is one this action chose, not input.
     */
    private function create(GoogleUser $account, string $googleId, string $email): User
    {
        return User::forceCreate([
            'name' => $this->name($account, $email),
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $account->getAvatar(),
            'password' => null,
            'role' => UserRole::Customer,
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);
    }

    /**
     * Keeps the local copy of the Google profile current, and links the account
     * on the first Google sign-in of a customer the shop already knew by email.
     */
    private function refresh(User $user, GoogleUser $account, string $googleId): User
    {
        $user->forceFill([
            'google_id' => $googleId,
            'avatar_url' => $account->getAvatar() ?? $user->avatar_url,
            'name' => $this->name($account, $user->name),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        return $user;
    }

    private function name(GoogleUser $account, string $fallback): string
    {
        return trim((string) $account->getName()) ?: $fallback;
    }
}
