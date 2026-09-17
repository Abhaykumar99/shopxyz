<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Static shop information pages. Wording is placeholder until the client supplies it.
 */
final class InfoPageController extends Controller
{
    public const PAGES = [
        'contact' => 'Contact us',
        'about' => 'About us',
        'shipping' => 'Delivery and shipping',
        'cancellation-refunds' => 'Cancellations and refunds',
        'terms' => 'Terms of use',
        'privacy' => 'Privacy policy',
    ];

    public function __invoke(string $page): View
    {
        return view("info.{$page}", [
            'title' => self::PAGES[$page],
            'pages' => self::PAGES,
            'current' => $page,
        ]);
    }
}
