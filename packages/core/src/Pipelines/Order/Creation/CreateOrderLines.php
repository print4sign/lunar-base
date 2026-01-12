<?php

namespace Lunar\Pipelines\Order\Creation;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Contracts\CartLine as CartLineContract;
use Lunar\Models\Contracts\Order as OrderContract;
use Lunar\Models\Contracts\OrderLine as OrderLineContract;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;
use Lunar\Models\Contracts\Supplier as SupplierContract;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\SupplierOrder;
use Lunar\Services\RuntimeSupplierSelector;
use Lunar\Utils\Arr;

class CreateOrderLines
{
    public function __construct(
        protected RuntimeSupplierSelector $supplierSelector
    ) {}

    /**
     * @param  Closure(OrderContract): mixed  $next
     */
    public function handle(OrderContract $order, Closure $next): mixed
    {
        /** @var Order $order */
        if (! $order->id) {
            $order->save();
        }

        $cart = $order->cart;

        $cart->recalculate();

        foreach ($cart->lines as $cartLine) {
            /** @var OrderLine $orderLine */
            $orderLine = $order->lines->first(function ($line) use ($cartLine) {
                $diff = Arr::diff($line->meta, $cartLine->meta);

                return empty($diff->new) &&
                    empty($diff->edited) &&
                    empty($diff->removed) &&
                    $line->purchasable_type == $cartLine->purchasable_type &&
                    $line->purchasable_id == $cartLine->purchasable_id;
            }) ?: App::make(OrderLineContract::class);

            $orderLine->fill([
                'order_id' => $order->id,
                'purchasable_type' => $cartLine->purchasable_type,
                'purchasable_id' => $cartLine->purchasable_id,
                'type' => $cartLine->purchasable->getType(),
                'description' => $cartLine->purchasable->getDescription(),
                'option' => $cartLine->purchasable->getOption(),
                'identifier' => $cartLine->purchasable->getIdentifier(),
                'unit_price' => $cartLine->unitPrice->value,
                'unit_quantity' => $cartLine->purchasable->getUnitQuantity(),
                'quantity' => $cartLine->quantity,
                'sub_total' => $cartLine->subTotal->value,
                'discount_total' => $cartLine->discountTotal?->value,
                'tax_breakdown' => $cartLine->taxBreakdown,
                'tax_total' => $cartLine->taxAmount->value,
                'total' => $cartLine->total->value,
                'notes' => null,
                'meta' => $cartLine->meta,
            ])->save();

            // Runtime supplier selection
            try {
                $variant = $orderLine->purchasable;

                $selection = $this->supplierSelector->selectBestSupplier(
                    $orderLine,
                    $order,
                    $this->getSelectionCriteria($order, $variant)
                );

                // Create supplier order with selected supplier
                $supplierOrderModel = App::make(\Lunar\Models\Contracts\SupplierOrder::class);

                $supplierOrderModel->fill([
                    'order_id' => $order->id,
                    'order_line_id' => $orderLine->id,
                    'supplier_id' => $selection->primary['supplier']->id,
                    'status' => SupplierOrder::STATUS_PENDING,
                    'estimated_cost_price' => $selection->primary['quote']['cost_price'],
                    'order_line_unit_price' => $orderLine->unit_price,
                    'order_line_total' => $orderLine->total,
                    'requires_approval' => $this->requiresApproval($variant, $cartLine),
                    'artwork_status' => $this->hasArtwork($cartLine) ? 'pending' : 'not_required',
                    'estimated_delivery_date' => $this->calculateDeliveryDate($selection->primary['quote']),
                    'cancellation_deadline' => $this->calculateCancellationDeadline($selection->primary['supplier']),
                    'external_data' => [
                        'selection_score' => $selection->primary['score'],
                        'selection_reason' => $selection->getReason(),
                        'alternatives' => $selection->fallbacks->take(2)->map(fn ($alt) => [
                            'supplier_id' => $alt['supplier']->id,
                            'supplier_name' => $alt['supplier']->name,
                            'score' => $alt['score'],
                            'cost_price' => $alt['quote']['cost_price'],
                        ])->toArray(),
                        'quote' => $selection->primary['quote'],
                        'selected_at' => now()->toIso8601String(),
                    ],
                ])->save();

            } catch (\Lunar\Exceptions\Suppliers\NoSuppliersAvailableException $e) {
                // Log but don't fail - allow orders without supplier selection
                Log::warning('No suppliers available for order line', [
                    'order_line_id' => $orderLine->id,
                    'order_id' => $order->id,
                    'variant_id' => $orderLine->purchasable_id,
                ]);

                // Continue without creating supplier order
            } catch (\Exception $e) {
                Log::error('Failed to select supplier for order line', [
                    'order_line_id' => $orderLine->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        return $next($order->refresh());
    }

    /**
     * Get selection criteria for supplier selection
     */
    protected function getSelectionCriteria(OrderContract $order, ProductVariantContract $variant): array
    {
        return [
            'weights' => [
                'price' => 40,
                'lead_time' => 25,
                'priority' => 20,
                'shipping_cost' => 15,
            ],
            'shipping_address' => $order->shippingAddress,
            'urgency' => $order->meta['urgency'] ?? 'normal',
        ];
    }

    /**
     * Determine if the order line requires approval
     */
    protected function requiresApproval(ProductVariantContract $variant, CartLineContract $cartLine): bool
    {
        // Check if cart line has artwork that needs approval
        if ($this->hasArtwork($cartLine)) {
            return true;
        }

        // Future: Add more approval rules (e.g., custom products, high-value orders)

        return false;
    }

    /**
     * Check if cart line has artwork
     */
    protected function hasArtwork(CartLineContract $cartLine): bool
    {
        // Check meta data for artwork/upload indicators
        $meta = $cartLine->meta ?? [];

        return isset($meta['has_artwork']) && $meta['has_artwork'] === true;
    }

    /**
     * Calculate estimated delivery date from quote
     */
    protected function calculateDeliveryDate(array $quote): ?Carbon
    {
        $leadTime = $quote['lead_time'] ?? null;

        if (! $leadTime) {
            return null;
        }

        // Lead time is in hours, convert to business days (8 hours per day)
        $businessDays = ceil($leadTime / 8);

        return now()->addWeekdays((int) $businessDays);
    }

    /**
     * Calculate cancellation deadline based on supplier settings
     */
    protected function calculateCancellationDeadline(SupplierContract $supplier): ?Carbon
    {
        $capabilities = $supplier->capabilities ?? [];
        $window = $capabilities['cancellation_window_hours'] ?? null;

        if (! $window) {
            return null;
        }

        return now()->addHours($window);
    }
}
