<?php

namespace App\Http\Controllers\Dev;

use App\Enums\PrintDocument;
use App\Enums\PrintFormat;
use App\Http\Controllers\Controller;
use App\Support\Demo\DemoData;
use App\Support\Icon;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Local-only design system preview (Phase 1). Not reachable in production.
 */
final class StyleguideController extends Controller
{
    public function index(Request $request): View
    {
        return view('dev.styleguide', [
            'products' => DemoData::products(),
            'categories' => DemoData::categories(),
            'address' => DemoData::address(),
            'order' => DemoData::order(),
            'steps' => DemoData::trackerSteps(),
            'deliveries' => DemoData::deliveries(),
            'icons' => Icon::available(),
            'paginator' => new LengthAwarePaginator(range(1, 3), 48, 12, 2, ['path' => $request->url()]),
        ]);
    }

    public function delivery(): View
    {
        return view('dev.delivery', [
            'deliveries' => DemoData::deliveries(),
            'order' => DemoData::order(),
        ]);
    }

    public function signIn(): View
    {
        return view('dev.sign-in');
    }

    public function print(Request $request, PrintDocument $document, ShopSettings $shop): View
    {
        $format = PrintFormat::tryFrom((string) $request->query('format'));

        if ($format === null || ! $format->supports($document)) {
            $format = $shop->printFormat($document);
        }

        $payment = $request->query('payment') === 'upi' ? 'upi' : 'cod';

        return view("pdf.{$document->value}", [
            'format' => $format,
            'order' => DemoData::order($payment),
        ]);
    }
}
