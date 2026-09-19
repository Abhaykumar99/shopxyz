<?php

namespace App\Support\Demo;

use App\Models\Address;
use App\Support\IndianPhone;

/**
 * TEMPORARY (Phases 2–4): replaced by the Address model.
 */
final readonly class DemoAddress
{
    public function __construct(
        public string $id,
        public string $label,
        public string $name,
        public string $phone,
        public string $line1,
        public ?string $line2,
        public ?string $landmark,
        public string $city,
        public string $state,
        public string $pincode,
        public bool $isDefault = false,
    ) {}

    /**
     * Snapshots a saved address onto a demo order. The customer's address book
     * is real from Phase 6; the order it is copied onto is not until Phase 8,
     * so the snapshot still travels as this value object.
     */
    public static function fromModel(Address $address): self
    {
        return new self(
            id: (string) $address->id,
            label: $address->label,
            name: $address->recipient_name,
            phone: $address->phone,
            line1: $address->line1,
            line2: $address->line2,
            landmark: $address->landmark,
            city: $address->city,
            state: $address->state,
            pincode: $address->pincode,
            isDefault: $address->is_default,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            label: (string) $data['label'],
            name: (string) $data['name'],
            phone: (string) $data['phone'],
            line1: (string) $data['line1'],
            line2: ($data['line2'] ?? null) ?: null,
            landmark: ($data['landmark'] ?? null) ?: null,
            city: (string) $data['city'],
            state: (string) $data['state'],
            pincode: (string) $data['pincode'],
            isDefault: (bool) ($data['is_default'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'name' => $this->name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'landmark' => $this->landmark,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'is_default' => $this->isDefault,
        ];
    }

    /**
     * Address lines for display (without the pincode).
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return array_values(array_filter([
            $this->line1,
            $this->line2,
            $this->landmark ? "Near {$this->landmark}" : null,
            "{$this->city}, {$this->state}",
        ]));
    }

    public function formattedPhone(): string
    {
        return IndianPhone::format($this->phone);
    }
}
