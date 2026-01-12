<?php

namespace Lunar\Services;

use Lunar\Models\Contracts\Order;
use Lunar\Models\Refund;
use Stripe\Stripe;
use Stripe\Refund as StripeRefund;

class RefundService
{
    public function processRefund(
        Order $order,
        int $amountCents,
        string $reason,
        string $method = 'original'
    ): Refund {

        // Create refund record
        $refund = Refund::create([
            'order_id' => $order->id,
            'amount' => $amountCents,
            'currency' => $order->currency_code,
            'method' => $method,
            'status' => 'pending',
            'reason' => $reason,
        ]);

        try {
            if ($method === 'original') {
                // Process through payment gateway
                $refund = $this->processOriginalMethodRefund($order, $refund);
            } elseif ($method === 'store_credit') {
                // Add to customer's store credit
                $refund = $this->processStoreCreditRefund($order, $refund);
            }

            $refund->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Refund processing failed', [
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);

            $refund->update([
                'status' => 'failed',
                'meta' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }

        return $refund;
    }

    protected function processOriginalMethodRefund(Order $order, Refund $refund): Refund
    {
        // Get payment intent from order
        $paymentIntentId = $order->meta['payment_intent'] ?? null;

        if (!$paymentIntentId) {
            throw new \Exception('No payment intent found for order');
        }

        // Process Stripe refund
        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeRefund = StripeRefund::create([
            'payment_intent' => $paymentIntentId,
            'amount' => $refund->amount->value,
            'reason' => 'requested_by_customer',
            'metadata' => [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
            ],
        ]);

        $refund->update([
            'payment_intent_id' => $paymentIntentId,
            'refund_id' => $stripeRefund->id,
            'status' => 'processing',
            'meta' => ['stripe_refund' => $stripeRefund->toArray()],
        ]);

        return $refund;
    }

    protected function processStoreCreditRefund(Order $order, Refund $refund): Refund
    {
        // TODO: Implement store credit system
        throw new \Exception('Store credit refunds not yet implemented');
    }
}
