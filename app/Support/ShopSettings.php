<?php

namespace App\Support;

use App\Enums\PrintDocument;
use App\Enums\PrintFormat;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Storage;

/**
 * Single source of shop details for views, PDFs and notifications (ADR-013).
 *
 * Phase 1 reads config/shop.php. From Phase 4 the admin-saved settings take
 * precedence, with the config values as fallback.
 */
final readonly class ShopSettings
{
    public function __construct(
        public string $name,
        public ?string $tagline,
        public ?string $phone,
        public ?string $whatsapp,
        public ?string $email,
        public ?string $address,
        public ?string $hours,
        public ?string $logoPath,
        public PrintFormat $labelFormat,
        public PrintFormat $invoiceFormat,
        public ?string $upiVpa = null,
        public ?string $upiPayeeName = null,
        public int $deliveryChargePaise = 0,
        public int $freeDeliveryAbovePaise = 0,
        public int $minOrderPaise = 0,
        public ?string $deliveryEta = null,
        public ?string $deliveryArea = null,
        /** @var list<string> */
        public array $servedPincodes = [],
    ) {}

    public static function fromConfig(Repository $config): self
    {
        return new self(
            name: (string) $config->get('shop.name'),
            tagline: $config->get('shop.tagline'),
            phone: $config->get('shop.phone'),
            whatsapp: $config->get('shop.whatsapp'),
            email: $config->get('shop.email'),
            address: $config->get('shop.address'),
            hours: $config->get('shop.hours'),
            logoPath: $config->get('shop.logo_path'),
            labelFormat: self::format($config->get('shop.print.label_format'), PrintDocument::Label),
            invoiceFormat: self::format($config->get('shop.print.invoice_format'), PrintDocument::Invoice),
            upiVpa: $config->get('shop.payment.upi_vpa'),
            upiPayeeName: $config->get('shop.payment.upi_payee_name'),
            deliveryChargePaise: (int) $config->get('shop.delivery.charge_paise', 0),
            freeDeliveryAbovePaise: (int) $config->get('shop.delivery.free_above_paise', 0),
            minOrderPaise: (int) $config->get('shop.delivery.min_order_paise', 0),
            deliveryEta: $config->get('shop.delivery.eta'),
            deliveryArea: $config->get('shop.delivery.area'),
            servedPincodes: array_values(array_map('strval', (array) $config->get('shop.delivery.pincodes', []))),
        );
    }

    /**
     * Delivery charge for a bag subtotal; free once the subtotal reaches the threshold.
     */
    public function deliveryChargeFor(int $subtotalPaise): int
    {
        if ($this->freeDeliveryAbovePaise > 0 && $subtotalPaise >= $this->freeDeliveryAbovePaise) {
            return 0;
        }

        return $this->deliveryChargePaise;
    }

    /**
     * How much more the customer needs to add for free delivery (0 when already free or not offered).
     */
    public function freeDeliveryShortfall(int $subtotalPaise): int
    {
        if ($this->freeDeliveryAbovePaise <= 0 || $this->deliveryChargePaise === 0) {
            return 0;
        }

        return max(0, $this->freeDeliveryAbovePaise - $subtotalPaise);
    }

    public function meetsMinimumOrder(int $subtotalPaise): bool
    {
        return $subtotalPaise >= $this->minOrderPaise;
    }

    /**
     * An empty list means every pincode is served.
     */
    public function servesPincode(string $pincode): bool
    {
        return $this->servedPincodes === [] || in_array(trim($pincode), $this->servedPincodes, true);
    }

    /**
     * UPI deep link (NPCI spec) for an exact amount with the order number as the note.
     * Used for the QR code and the "Pay with UPI app" button on phones.
     */
    public function upiPaymentUri(int $amountPaise, string $orderNumber): ?string
    {
        if (! $this->upiVpa) {
            return null;
        }

        return 'upi://pay?'.http_build_query([
            'pa' => $this->upiVpa,
            'pn' => $this->upiPayeeName ?? $this->name,
            'am' => number_format($amountPaise / 100, 2, '.', ''),
            'cu' => 'INR',
            'tn' => $orderNumber,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function printFormat(PrintDocument $document): PrintFormat
    {
        return match ($document) {
            PrintDocument::Label => $this->labelFormat,
            PrintDocument::Invoice => $this->invoiceFormat,
        };
    }

    public function logoUrl(): ?string
    {
        return $this->logoPath ? Storage::disk('public')->url($this->logoPath) : null;
    }

    /**
     * Up to two letters used as a monogram when no logo is set.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        return mb_strtoupper(collect($words)->take(2)->map(fn (string $word): string => mb_substr($word, 0, 1))->implode(''));
    }

    /**
     * Digits-only WhatsApp number for wa.me links.
     */
    public function whatsappLink(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp);

        return $digits ? "https://wa.me/{$digits}" : null;
    }

    private static function format(mixed $value, PrintDocument $document): PrintFormat
    {
        $format = is_string($value) ? PrintFormat::tryFrom($value) : null;

        return $format !== null && $format->supports($document) ? $format : PrintFormat::defaultFor($document);
    }
}
