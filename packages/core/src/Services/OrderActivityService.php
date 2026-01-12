<?php

namespace Lunar\Services;

use Illuminate\Support\Collection;
use Lunar\Models\Contracts\Order;

class OrderActivityService
{
    /**
     * Get complete timeline for an order including all supplier orders
     */
    public function getOrderTimeline(Order $order): Collection
    {
        $timeline = collect();

        // Order placed
        $timeline->push([
            'type' => 'order_placed',
            'timestamp' => $order->created_at,
            'title' => 'Order Placed',
            'description' => "Order #{$order->reference} has been placed",
            'status' => 'completed',
        ]);

        // Supplier order activities
        foreach ($order->supplierOrders as $supplierOrder) {
            $supplierName = $supplierOrder->supplier->name;

            // Submitted
            if ($supplierOrder->submitted_at) {
                $timeline->push([
                    'type' => 'supplier_submitted',
                    'timestamp' => $supplierOrder->submitted_at,
                    'title' => 'Submitted to Supplier',
                    'description' => "Order submitted to {$supplierName}",
                    'status' => 'completed',
                    'supplier_order_id' => $supplierOrder->id,
                ]);
            }

            // Approved
            if ($supplierOrder->approved_at) {
                $timeline->push([
                    'type' => 'artwork_approved',
                    'timestamp' => $supplierOrder->approved_at,
                    'title' => 'Artwork Approved',
                    'description' => "Artwork approved for {$supplierName}",
                    'status' => 'completed',
                    'supplier_order_id' => $supplierOrder->id,
                ]);
            }

            // Shipped
            if ($supplierOrder->shipped_at) {
                $timeline->push([
                    'type' => 'shipped',
                    'timestamp' => $supplierOrder->shipped_at,
                    'title' => 'Shipped',
                    'description' => "Order shipped by {$supplierName}",
                    'status' => 'completed',
                    'supplier_order_id' => $supplierOrder->id,
                    'tracking_url' => $supplierOrder->getTrackingUrl(),
                ]);
            }

            // Delivered
            if ($supplierOrder->delivered_at) {
                $timeline->push([
                    'type' => 'delivered',
                    'timestamp' => $supplierOrder->delivered_at,
                    'title' => 'Delivered',
                    'description' => "Order delivered from {$supplierName}",
                    'status' => 'completed',
                    'supplier_order_id' => $supplierOrder->id,
                ]);
            }

            // Cancelled
            if ($supplierOrder->cancelled_at) {
                $timeline->push([
                    'type' => 'cancelled',
                    'timestamp' => $supplierOrder->cancelled_at,
                    'title' => 'Cancelled',
                    'description' => "Order from {$supplierName} cancelled: {$supplierOrder->cancellation_reason}",
                    'status' => 'cancelled',
                    'supplier_order_id' => $supplierOrder->id,
                ]);
            }

            // Estimated delivery
            if ($supplierOrder->estimated_delivery_date && !$supplierOrder->delivered_at) {
                $timeline->push([
                    'type' => 'estimated_delivery',
                    'timestamp' => $supplierOrder->estimated_delivery_date->startOfDay(),
                    'title' => 'Estimated Delivery',
                    'description' => "Expected delivery from {$supplierName}",
                    'status' => 'pending',
                    'supplier_order_id' => $supplierOrder->id,
                ]);
            }
        }

        return $timeline->sortBy('timestamp')->values();
    }

    /**
     * Get current status summary for customer portal
     */
    public function getOrderStatus(Order $order): array
    {
        $supplierOrders = $order->supplierOrders;

        $total = $supplierOrders->count();
        $submitted = $supplierOrders->where('status', '!=', 'pending')->count();
        $shipped = $supplierOrders->whereNotNull('shipped_at')->count();
        $delivered = $supplierOrders->whereNotNull('delivered_at')->count();
        $cancelled = $supplierOrders->whereNotNull('cancelled_at')->count();

        // Determine overall status
        if ($delivered === $total) {
            $status = 'delivered';
            $progress = 100;
        } elseif ($cancelled > 0 && $delivered + $cancelled === $total) {
            $status = 'completed_with_cancellations';
            $progress = 100;
        } elseif ($shipped > 0) {
            $status = 'in_transit';
            $progress = 60 + (($shipped / $total) * 30);
        } elseif ($submitted > 0) {
            $status = 'processing';
            $progress = 30 + (($submitted / $total) * 30);
        } else {
            $status = 'pending';
            $progress = 10;
        }

        return [
            'status' => $status,
            'progress' => (int) $progress,
            'counts' => [
                'total' => $total,
                'submitted' => $submitted,
                'shipped' => $shipped,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
            ],
            'can_cancel' => $supplierOrders->filter->canBeCancelled()->count() > 0,
        ];
    }

    /**
     * Check if customer can request cancellation
     */
    public function canRequestCancellation(Order $order): bool
    {
        return $order->supplierOrders->filter->canBeCancelled()->count() > 0;
    }

    /**
     * Get cancellable supplier orders with deadline info
     */
    public function getCancellableOrders(Order $order): Collection
    {
        return $order->supplierOrders
            ->filter->canBeCancelled()
            ->map(function ($supplierOrder) {
                return [
                    'supplier_order_id' => $supplierOrder->id,
                    'supplier_name' => $supplierOrder->supplier->name,
                    'cancellation_fee' => $supplierOrder->getCancellationFee(),
                    'time_remaining' => $supplierOrder->getCancellationTimeRemaining(),
                    'deadline' => $supplierOrder->cancellation_deadline,
                ];
            })
            ->values();
    }
}
