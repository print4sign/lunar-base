<?php

namespace Lunar\Services;

use Lunar\DataTransferObjects\CancellationResult;
use Lunar\DataTransferObjects\SupplierOrderCancellationResult;
use Lunar\Events\SupplierOrderCancelled;
use Lunar\Managers\SupplierManager;
use Lunar\Models\Contracts\Order;
use Lunar\Models\Contracts\SupplierOrder;
use Lunar\Models\Contracts\User;

class OrderCancellationService
{
    public function __construct(
        protected SupplierManager $suppliers,
        protected RefundService $refunds
    ) {}

    /**
     * Cancel an entire order (all supplier orders)
     */
    public function cancelOrder(
        Order $order,
        User $user,
        string $reason,
        bool $customerInitiated = false
    ): CancellationResult {

        $results = [
            'cancelled' => [],
            'failed' => [],
            'refunds' => [],
        ];

        foreach ($order->supplierOrders as $supplierOrder) {
            try {
                $result = $this->cancelSupplierOrder(
                    $supplierOrder,
                    $user,
                    $reason,
                    $customerInitiated
                );

                if ($result->success) {
                    $results['cancelled'][] = $supplierOrder;
                    if ($result->refund) {
                        $results['refunds'][] = $result->refund;
                    }
                } else {
                    $results['failed'][] = [
                        'supplier_order' => $supplierOrder,
                        'reason' => $result->error,
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'supplier_order' => $supplierOrder,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        // Update main order status
        if (count($results['cancelled']) === $order->supplierOrders->count()) {
            $order->update(['status' => 'cancelled']);
        } elseif (count($results['cancelled']) > 0) {
            $order->update(['status' => 'partially_cancelled']);
        }

        return new CancellationResult(
            success: count($results['failed']) === 0,
            cancelled: collect($results['cancelled']),
            failed: collect($results['failed']),
            totalRefund: collect($results['refunds'])->sum('amount'),
        );
    }

    /**
     * Cancel a single supplier order
     */
    public function cancelSupplierOrder(
        SupplierOrder $supplierOrder,
        User $user,
        string $reason,
        bool $customerInitiated = false
    ): SupplierOrderCancellationResult {

        // Check if cancellable
        if (!$supplierOrder->canBeCancelled()) {
            return SupplierOrderCancellationResult::failed(
                'Order cannot be cancelled at this stage',
                $supplierOrder
            );
        }

        // Calculate cancellation fee
        $cancellationFee = $supplierOrder->getCancellationFee();

        // Try to cancel with supplier API
        $supplierCancelled = false;

        if ($supplierOrder->external_order_id) {
            try {
                $driver = $this->suppliers->supplier($supplierOrder->supplier);
                $supplierCancelled = $driver->cancelOrder($supplierOrder->external_order_id);
            } catch (\Exception $e) {
                \Log::error('Failed to cancel with supplier', [
                    'supplier_order_id' => $supplierOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update supplier order
        $supplierOrder->update([
            'status' => SupplierOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
            'cancellation_fee' => $cancellationFee,
            'cancellation_requested_at' => now(),
        ]);

        // Calculate refund
        $orderLineTotal = $supplierOrder->order_line_total;
        $refundAmount = $orderLineTotal - $cancellationFee;

        $refund = null;
        if ($refundAmount > 0 && $customerInitiated) {
            $refund = $this->refunds->processRefund(
                $supplierOrder->order,
                $refundAmount,
                "Cancelled: {$reason}"
            );

            $supplierOrder->update([
                'refund_amount' => $refundAmount,
                'refund_issued_at' => now(),
                'refund_reason' => $reason,
            ]);
        }

        // Log activity
        activity('supplier_orders')
            ->performedOn($supplierOrder)
            ->causedBy($user)
            ->withProperties([
                'cancelled_by_customer' => $customerInitiated,
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount,
                'supplier_cancelled' => $supplierCancelled,
            ])
            ->log('Supplier order cancelled');

        // Notify customer
        if ($customerInitiated) {
            event(new SupplierOrderCancelled($supplierOrder, $refund));
        }

        return SupplierOrderCancellationResult::success(
            $supplierOrder,
            $supplierCancelled,
            $cancellationFee,
            $refund
        );
    }
}
