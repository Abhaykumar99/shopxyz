<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PrintDocument;
use App\Enums\PrintFormat;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prints an order's labels and invoice from the admin panel, using the same
 * templates as the rest of the shop (ADR-014). One label per box (ADR-021).
 */
final class PrintController extends Controller
{
    public function label(Request $request, Order $order, ShopSettings $shop): View
    {
        $order->load(['packages.items.item', 'items']);

        $data = [
            'format' => $this->format($request, PrintDocument::Label, $shop),
            'order' => $this->payload($order),
        ];

        // Every box by default, one label per page; `?box=2` reprints just that one.
        if ($request->filled('box')) {
            $boxes = max(1, $order->packages->count());
            $data['box'] = max(1, min($boxes, (int) $request->query('box'))) - 1;
        }

        return view('pdf.label', $data);
    }

    public function invoice(Request $request, Order $order, ShopSettings $shop): View
    {
        $order->load(['items', 'invoice']);

        return view('pdf.invoice', [
            'format' => $this->format($request, PrintDocument::Invoice, $shop),
            'order' => $this->payload($order),
        ]);
    }

    /**
     * Streams a payment screenshot from the private disk to the admin. The
     * content type is pinned to a real image type and sniffing is switched off,
     * so a file that only claims to be an image cannot run in the admin's tab.
     */
    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        $disk = Storage::disk('local');
        $mime = $disk->mimeType($payment->proof_path) ?: '';
        $isImage = in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);

        return $disk->response(
            $payment->proof_path,
            'payment-proof-'.$payment->getKey(),
            [
                'Content-Type' => $isImage ? $mime : 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'",
            ],
            $isImage ? 'inline' : 'attachment',
        );
    }

    /**
     * The boxes this order was packed into, each with what is inside it (ADR-021).
     *
     * @return list<array{id: string, code: string, items: list<array{name: string, variant: string, quantity: int}>}>
     */
    private function packages(Order $order): array
    {
        $packages = [];

        foreach ($order->packages as $package) {
            $items = [];

            foreach ($package->items as $packageItem) {
                $items[] = [
                    'name' => (string) $packageItem->item?->product_name,
                    'variant' => (string) $packageItem->item?->variant_name,
                    'quantity' => $packageItem->quantity,
                ];
            }

            $packages[] = [
                'id' => $package->package_id,
                'code' => (string) $package->pickup_code,
                'items' => $items,
            ];
        }

        return $packages;
    }

    private function format(Request $request, PrintDocument $document, ShopSettings $shop): PrintFormat
    {
        $format = PrintFormat::tryFrom((string) $request->query('format'));

        return $format !== null && $format->supports($document) ? $format : $shop->printFormat($document);
    }

    /**
     * The shape the print templates expect (see `App\Support\Demo\DemoData::order`).
     *
     * @return array<string, mixed>
     */
    private function payload(Order $order): array
    {
        $items = $order->items->map(fn ($item): array => [
            'name' => $item->product_name,
            'variant' => $item->variant_name,
            'sku' => $item->sku,
            'quantity' => $item->quantity,
            'mrp' => $item->mrp_paise,
            'paise' => $item->unit_price_paise,
        ])->all();

        return [
            'number' => $order->order_number,
            'invoice_number' => $order->invoice?->invoice_number,
            'placed_at' => $order->placed_at->format('D, j M Y, g:i a'),
            'payment_method' => $order->payment_method->value,
            'payment_status' => $order->payment_status->label(),
            'customer' => [
                'label' => 'Delivery address',
                'name' => $order->ship_name,
                'phone' => '+91 '.$order->ship_phone,
                'lines' => array_values(array_filter([
                    $order->ship_line1,
                    $order->ship_line2,
                    $order->ship_landmark,
                    $order->ship_city.', '.$order->ship_state,
                ])),
                'pincode' => $order->ship_pincode,
            ],
            'items' => $items,
            'mrp_total' => $order->subtotal_paise + $order->discount_paise,
            'subtotal' => $order->subtotal_paise,
            'discount' => $order->discount_paise,
            'delivery' => $order->delivery_charge_paise,
            'total' => $order->total_paise,
            'note' => $order->customer_note,
            'packages' => $this->packages($order),
        ];
    }
}
