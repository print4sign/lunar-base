<?php

namespace Lunar\Pipelines\Order\Creation;

use Closure;
use Lunar\Models\Contracts\Order as OrderContract;
use Lunar\Models\Order;
use Lunar\Services\PrintAssetService;

/**
 * Snapshot upload specifications and copy print assets during order creation.
 *
 * This pipeline:
 * 1. Snapshots the resolved upload spec from each cart line to the order line meta
 * 2. Copies any uploaded print assets from cart lines to order lines
 *
 * This ensures the upload requirements and files are preserved with the order
 * even if the product configuration changes later.
 */
class SnapshotUploadSpec
{
    public function __construct(
        protected PrintAssetService $printAssetService
    ) {}

    /**
     * @param  Closure(OrderContract): mixed  $next
     */
    public function handle(OrderContract $order, Closure $next): mixed
    {
        /** @var Order $order */
        $cart = $order->cart;

        if (! $cart) {
            return $next($order);
        }

        foreach ($cart->lines as $cartLine) {
            // Find the corresponding order line
            $orderLine = $order->lines->first(function ($line) use ($cartLine) {
                return $line->purchasable_type === $cartLine->purchasable_type
                    && $line->purchasable_id === $cartLine->purchasable_id;
            });

            if (! $orderLine) {
                continue;
            }

            // Resolve and snapshot the upload spec
            $uploadSpec = $cartLine->resolveUploadSpec();

            if ($uploadSpec) {
                $meta = $orderLine->meta ?? [];
                $meta['upload_spec'] = $uploadSpec->toArray();
                $orderLine->meta = $meta;
                $orderLine->save();
            }

            // Copy print assets from cart line to order line
            if ($cartLine->printAssets->isNotEmpty()) {
                $this->printAssetService->copyToOrderLine($cartLine, $orderLine);
            }
        }

        return $next($order);
    }
}
