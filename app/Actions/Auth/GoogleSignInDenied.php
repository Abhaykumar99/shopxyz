<?php

namespace App\Actions\Auth;

use RuntimeException;

/**
 * Google authenticated someone, but the shop will not sign them in: the email
 * is unverified, the account belongs to staff, or it has been closed or
 * switched off (ADR-004). The message is safe to show the visitor.
 */
final class GoogleSignInDenied extends RuntimeException
{
    public static function unverifiedEmail(): self
    {
        return new self('That Google account does not have a verified email address, so we cannot use it to sign in.');
    }

    public static function staffAccount(): self
    {
        return new self('That email belongs to a shop account. Staff sign in from the staff pages, not with Google.');
    }

    public static function closedAccount(): self
    {
        return new self('That account is no longer active. Please contact the shop.');
    }

    public static function incomplete(): self
    {
        return new self('Google did not share enough of your account to sign you in. Please try again.');
    }
}
