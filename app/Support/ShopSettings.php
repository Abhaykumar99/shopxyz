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
        );
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
