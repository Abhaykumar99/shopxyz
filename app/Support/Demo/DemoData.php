<?php

namespace App\Support\Demo;

/**
 * TEMPORARY dummy content for the UI-first phases (1–3).
 *
 * Replaced by database models and seeders in Phases 4–5. Brand names are
 * fictional on purpose. Amounts are integer paise (ADR-005).
 */
final class DemoData
{
    /**
     * @return list<array{name: string, slug: string, count: int}>
     */
    public static function categories(): array
    {
        return [
            ['name' => 'Cosmetics', 'slug' => 'cosmetics', 'count' => 124],
            ['name' => 'Confectionery', 'slug' => 'confectionery', 'count' => 86],
            ['name' => 'Gifts', 'slug' => 'gifts', 'count' => 52],
        ];
    }

    /**
     * @return list<array{name: string, category: string, brand: string, variant: string, paise: int, mrp: int|null, in_stock: bool}>
     */
    public static function products(): array
    {
        return [
            ['name' => 'Velvet Matte Lipstick, Rosewood', 'category' => 'cosmetics', 'brand' => 'Blush & Bloom', 'variant' => '4.2 g', 'paise' => 34900, 'mrp' => 49900, 'in_stock' => true],
            ['name' => 'Kaju Katli, classic silver leaf', 'category' => 'confectionery', 'brand' => 'Mithai Ghar', 'variant' => '500 g box', 'paise' => 52000, 'mrp' => null, 'in_stock' => true],
            ['name' => 'Festive dry fruit hamper with brass diya', 'category' => 'gifts', 'brand' => 'Utsav Gifting', 'variant' => 'Medium', 'paise' => 149900, 'mrp' => 179900, 'in_stock' => true],
            ['name' => 'Aloe and cucumber face gel', 'category' => 'cosmetics', 'brand' => 'Green Leaf', 'variant' => '100 ml', 'paise' => 19950, 'mrp' => 22000, 'in_stock' => false],
            ['name' => 'Assorted dark chocolate box', 'category' => 'confectionery', 'brand' => 'Cocoa Lane', 'variant' => '16 pieces', 'paise' => 69900, 'mrp' => 79900, 'in_stock' => true],
            ['name' => 'Scented candle trio, sandalwood and jasmine', 'category' => 'gifts', 'brand' => 'Diya House', 'variant' => 'Set of 3', 'paise' => 89900, 'mrp' => null, 'in_stock' => true],
            ['name' => 'Kohl kajal, smudge-proof', 'category' => 'cosmetics', 'brand' => 'Blush & Bloom', 'variant' => '0.35 g', 'paise' => 14900, 'mrp' => 19900, 'in_stock' => true],
            ['name' => 'Rose gulkand ladoo', 'category' => 'confectionery', 'brand' => 'Mithai Ghar', 'variant' => '250 g', 'paise' => 28000, 'mrp' => null, 'in_stock' => true],
        ];
    }

    /**
     * @return array{name: string, phone: string, lines: list<string>, pincode: string, label: string}
     */
    public static function address(): array
    {
        return [
            'label' => 'Home',
            'name' => 'Priya Sharma',
            'phone' => '+91 98300 12345',
            'lines' => ['Flat 3B, Shanti Apartments', 'Boring Road, near Pani Tanki', 'Patna, Bihar'],
            'pincode' => '800001',
        ];
    }

    /**
     * A placed order as the print templates and order pages expect it.
     *
     * @return array<string, mixed>
     */
    public static function order(string $paymentMethod = 'cod'): array
    {
        $items = [
            ['name' => 'Velvet Matte Lipstick, Rosewood', 'variant' => '4.2 g', 'sku' => 'BB-LIP-RW', 'quantity' => 2, 'mrp' => 49900, 'paise' => 34900],
            ['name' => 'Kaju Katli, classic silver leaf', 'variant' => '500 g box', 'sku' => 'MG-KK-500', 'quantity' => 1, 'mrp' => 52000, 'paise' => 52000],
            ['name' => 'Scented candle trio, sandalwood and jasmine', 'variant' => 'Set of 3', 'sku' => 'DH-CND-3', 'quantity' => 1, 'mrp' => 89900, 'paise' => 89900],
        ];

        $mrpTotal = array_sum(array_map(fn (array $item): int => $item['mrp'] * $item['quantity'], $items));
        $subtotal = array_sum(array_map(fn (array $item): int => $item['paise'] * $item['quantity'], $items));
        $delivery = 4000;

        return [
            'number' => 'ORD-10245',
            'invoice_number' => 'INV/2026-27/00018',
            'placed_at' => 'Thu, 17 Sep 2026, 10:42 am',
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'upi' ? 'Paid (UPI verified)' : 'To be collected',
            'customer' => self::address(),
            'items' => $items,
            'mrp_total' => $mrpTotal,
            'subtotal' => $subtotal,
            'discount' => $mrpTotal - $subtotal,
            'delivery' => $delivery,
            'total' => $subtotal + $delivery,
            'note' => 'Please call before arriving. Gate code 4411.',
        ];
    }

    /**
     * @return list<array{label: string, time?: string, state: string, icon?: string, note?: string}>
     */
    public static function trackerSteps(): array
    {
        return [
            ['label' => 'Order placed', 'time' => 'Today, 10:42 am', 'state' => 'done'],
            ['label' => 'Confirmed', 'time' => 'Today, 10:55 am', 'state' => 'done'],
            ['label' => 'Packed', 'time' => 'Today, 11:30 am', 'state' => 'done'],
            ['label' => 'Out for delivery', 'time' => 'Today, 12:10 pm', 'state' => 'current', 'icon' => 'truck', 'note' => 'Rahul Kumar is on the way. Call +91 90000 11111.'],
            ['label' => 'Delivered', 'state' => 'upcoming'],
        ];
    }

    /**
     * @return list<array{number: string, customer: string, area: string, pincode: string, items: int, cod: int|null, collected: bool, status: string, tone: string}>
     */
    public static function deliveries(): array
    {
        return [
            ['number' => 'ORD-10245', 'customer' => 'Priya Sharma', 'area' => 'Boring Road', 'pincode' => '800001', 'items' => 4, 'cod' => 215700, 'collected' => false, 'status' => 'Out for delivery', 'tone' => 'berry'],
            ['number' => 'ORD-10248', 'customer' => 'Aman Verma', 'area' => 'Kankarbagh', 'pincode' => '800020', 'items' => 1, 'cod' => null, 'collected' => false, 'status' => 'Assigned', 'tone' => 'info'],
            ['number' => 'ORD-10231', 'customer' => 'Neha Singh', 'area' => 'Rajendra Nagar', 'pincode' => '800016', 'items' => 2, 'cod' => 64000, 'collected' => true, 'status' => 'Delivered', 'tone' => 'success'],
        ];
    }
}
