<?php

namespace Lunar\Base\DataTransferObjects\Supplier;

class ShippingOptionResponse
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
        public readonly int $priceInCents,
        public readonly string $currency,
        public readonly ?string $description = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly ?string $deliveryDateFrom = null,
        public readonly ?string $deliveryDateTo = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * Create from Probo delivery option response.
     */
    public static function fromProboOption(array $option): self
    {
        $identifier = $option['code'] ?? $option['id'] ?? $option['method'] ?? uniqid('probo_');

        return new self(
            identifier: 'probo_' . $identifier,
            name: $option['name']
                ?? $option['description']
                ?? $option['translations']['en']['title']
                ?? 'Probo Shipping',
            priceInCents: (int) (($option['price'] ?? $option['cost'] ?? 0) * 100),
            currency: $option['currency'] ?? 'EUR',
            description: $option['description']
                ?? $option['translations']['en']['description']
                ?? null,
            estimatedDelivery: $option['estimated_delivery']
                ?? $option['delivery_time']
                ?? null,
            deliveryDateFrom: $option['delivery_date_from']
                ?? $option['estimated_date_from']
                ?? null,
            deliveryDateTo: $option['delivery_date_to']
                ?? $option['estimated_date_to']
                ?? null,
            meta: $option,
        );
    }

    /**
     * Create from array (e.g., from cache/serialized data).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            identifier: $data['identifier'],
            name: $data['name'],
            priceInCents: $data['price_in_cents'] ?? $data['priceInCents'],
            currency: $data['currency'] ?? 'EUR',
            description: $data['description'] ?? null,
            estimatedDelivery: $data['estimated_delivery'] ?? $data['estimatedDelivery'] ?? null,
            deliveryDateFrom: $data['delivery_date_from'] ?? $data['deliveryDateFrom'] ?? null,
            deliveryDateTo: $data['delivery_date_to'] ?? $data['deliveryDateTo'] ?? null,
            meta: $data['meta'] ?? null,
        );
    }

    /**
     * Check if this is a Probo shipping option.
     */
    public function isProboShipping(): bool
    {
        return str_starts_with($this->identifier, 'probo_');
    }

    /**
     * Get the Probo-specific identifier (without prefix).
     */
    public function getProboIdentifier(): ?string
    {
        if (! $this->isProboShipping()) {
            return null;
        }

        return str_replace('probo_', '', $this->identifier);
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->priceInCents / 100, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted delivery estimate.
     */
    public function getDeliveryEstimate(): ?string
    {
        if ($this->estimatedDelivery) {
            return $this->estimatedDelivery;
        }

        if ($this->deliveryDateFrom && $this->deliveryDateTo) {
            return $this->deliveryDateFrom . ' - ' . $this->deliveryDateTo;
        }

        if ($this->deliveryDateFrom) {
            return 'From ' . $this->deliveryDateFrom;
        }

        return null;
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'price_in_cents' => $this->priceInCents,
            'currency' => $this->currency,
            'description' => $this->description,
            'estimated_delivery' => $this->estimatedDelivery,
            'delivery_date_from' => $this->deliveryDateFrom,
            'delivery_date_to' => $this->deliveryDateTo,
            'meta' => $this->meta,
        ];
    }
}
