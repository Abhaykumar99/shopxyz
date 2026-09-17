<?php

namespace App\Enums;

/**
 * Paper formats for printed documents (ADR-014).
 */
enum PrintFormat: string
{
    case Thermal4x6 = 'thermal_4x6';
    case A5 = 'a5';
    case A4 = 'a4';

    public function label(): string
    {
        return match ($this) {
            self::Thermal4x6 => '4×6" thermal label',
            self::A5 => 'A5 paper',
            self::A4 => 'A4 paper',
        };
    }

    /**
     * Page width and height in millimetres (portrait).
     *
     * @return array{width: int, height: int}
     */
    public function dimensions(): array
    {
        return match ($this) {
            self::Thermal4x6 => ['width' => 100, 'height' => 150],
            self::A5 => ['width' => 148, 'height' => 210],
            self::A4 => ['width' => 210, 'height' => 297],
        };
    }

    /**
     * Value for the CSS `@page { size: ... }` rule.
     */
    public function cssPageSize(): string
    {
        ['width' => $width, 'height' => $height] = $this->dimensions();

        return "{$width}mm {$height}mm";
    }

    public function supports(PrintDocument $document): bool
    {
        return in_array($this, self::forDocument($document), true);
    }

    /**
     * @return list<self>
     */
    public static function forDocument(PrintDocument $document): array
    {
        return match ($document) {
            PrintDocument::Label => [self::Thermal4x6, self::A5, self::A4],
            PrintDocument::Invoice => [self::A4, self::A5],
        };
    }

    public static function defaultFor(PrintDocument $document): self
    {
        return match ($document) {
            PrintDocument::Label => self::Thermal4x6,
            PrintDocument::Invoice => self::A4,
        };
    }
}
