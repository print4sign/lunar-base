<?php

namespace Lunar\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Lunar\DataTypes\Price;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Currency;
use Lunar\Models\OrderLine;

class DeliverLaterService
{
    /**
     * Default surcharge in cents (€15.00).
     */
    public const DEFAULT_SURCHARGE_CENTS = 1500;

    /**
     * Default token validity in days.
     */
    public const DEFAULT_TOKEN_VALIDITY_DAYS = 7;

    /**
     * Get the surcharge amount in cents.
     */
    public function getSurchargeAmount(): int
    {
        return config('lunar.cart.print_assets.deliver_later_surcharge', self::DEFAULT_SURCHARGE_CENTS);
    }

    /**
     * Get the token validity in days.
     */
    public function getTokenValidityDays(): int
    {
        return config('lunar.cart.print_assets.upload_token_validity_days', self::DEFAULT_TOKEN_VALIDITY_DAYS);
    }

    /**
     * Check if deliver-later is enabled.
     */
    public function isEnabled(): bool
    {
        return config('lunar.cart.print_assets.deliver_later_enabled', true);
    }

    /**
     * Mark a cart line for deliver-later uploads.
     */
    public function markForDeliverLater(CartLine $cartLine): void
    {
        $meta = $cartLine->meta ?? [];
        $meta['upload_deliver_later'] = true;
        $meta['upload_method'] = 'deliver_later';

        $cartLine->update(['meta' => $meta]);

        Log::info('DeliverLaterService: Cart line marked for deliver-later', [
            'cart_line_id' => $cartLine->id,
        ]);
    }

    /**
     * Unmark a cart line from deliver-later.
     */
    public function unmarkDeliverLater(CartLine $cartLine): void
    {
        $meta = $cartLine->meta ?? [];
        unset($meta['upload_deliver_later']);

        if (($meta['upload_method'] ?? null) === 'deliver_later') {
            unset($meta['upload_method']);
        }

        $cartLine->update(['meta' => $meta]);

        Log::info('DeliverLaterService: Cart line unmarked from deliver-later', [
            'cart_line_id' => $cartLine->id,
        ]);
    }

    /**
     * Check if a cart line is marked for deliver-later.
     */
    public function isMarkedForDeliverLater(CartLine $cartLine): bool
    {
        return ($cartLine->meta['upload_deliver_later'] ?? false) === true;
    }

    /**
     * Add a surcharge line to the cart for deliver-later.
     *
     * @return CartLine The surcharge cart line
     */
    public function addSurchargeToCart(Cart $cart, CartLine $forLine): CartLine
    {
        // Check if surcharge already exists for this line
        $existingSurcharge = $cart->lines()
            ->where('meta->surcharge_for_line_id', $forLine->id)
            ->where('type', 'upload_surcharge')
            ->first();

        if ($existingSurcharge) {
            Log::debug('DeliverLaterService: Surcharge already exists', [
                'cart_line_id' => $forLine->id,
                'surcharge_line_id' => $existingSurcharge->id,
            ]);

            return $existingSurcharge;
        }

        $surchargeAmount = $this->getSurchargeAmount();
        $currency = $cart->currency ?? Currency::getDefault();

        $surchargeLine = $cart->lines()->create([
            'purchasable_type' => null,
            'purchasable_id' => null,
            'quantity' => 1,
            'type' => 'upload_surcharge',
            'meta' => [
                'surcharge_for_line_id' => $forLine->id,
                'description' => __('Deliver files later surcharge'),
                'unit_price' => $surchargeAmount,
                'currency_code' => $currency->code,
            ],
        ]);

        Log::info('DeliverLaterService: Surcharge added to cart', [
            'cart_id' => $cart->id,
            'cart_line_id' => $forLine->id,
            'surcharge_line_id' => $surchargeLine->id,
            'amount' => $surchargeAmount,
        ]);

        return $surchargeLine;
    }

    /**
     * Remove the surcharge line from the cart.
     */
    public function removeSurchargeFromCart(Cart $cart, CartLine $forLine): void
    {
        $deleted = $cart->lines()
            ->where('meta->surcharge_for_line_id', $forLine->id)
            ->where('type', 'upload_surcharge')
            ->delete();

        if ($deleted) {
            Log::info('DeliverLaterService: Surcharge removed from cart', [
                'cart_id' => $cart->id,
                'cart_line_id' => $forLine->id,
            ]);
        }
    }

    /**
     * Generate a secure upload token for an order line.
     */
    public function generateUploadToken(OrderLine $orderLine): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays($this->getTokenValidityDays());

        $orderLine->update([
            'upload_deliver_later' => true,
            'upload_token' => $token,
            'upload_token_expires_at' => $expiresAt,
        ]);

        Log::info('DeliverLaterService: Upload token generated', [
            'order_line_id' => $orderLine->id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return $token;
    }

    /**
     * Validate an upload token and return the order line.
     */
    public function validateToken(string $token): ?OrderLine
    {
        $orderLine = OrderLine::where('upload_token', $token)
            ->whereNull('upload_completed_at')
            ->where(function ($query) {
                $query->whereNull('upload_token_expires_at')
                    ->orWhere('upload_token_expires_at', '>', now());
            })
            ->first();

        if (! $orderLine) {
            Log::warning('DeliverLaterService: Invalid or expired token', [
                'token' => Str::limit($token, 10, '...'),
            ]);

            return null;
        }

        return $orderLine;
    }

    /**
     * Get the upload URL for an order line.
     */
    public function getUploadUrl(OrderLine $orderLine): ?string
    {
        $token = $orderLine->upload_token;

        if (! $token) {
            return null;
        }

        return route('upload.post-order', ['token' => $token]);
    }

    /**
     * Send the upload link email to the customer.
     */
    public function sendUploadLinkEmail(OrderLine $orderLine): void
    {
        $order = $orderLine->order;
        $uploadUrl = $this->getUploadUrl($orderLine);

        if (! $uploadUrl) {
            Log::warning('DeliverLaterService: Cannot send email - no upload URL', [
                'order_line_id' => $orderLine->id,
            ]);

            return;
        }

        $email = $order->billingAddress?->contact_email
            ?? $order->shippingAddress?->contact_email
            ?? $order->customer?->email;

        if (! $email) {
            Log::warning('DeliverLaterService: Cannot send email - no email address', [
                'order_line_id' => $orderLine->id,
            ]);

            return;
        }

        // Use the UploadLinkMail mailable if it exists
        $mailableClass = config('lunar.cart.print_assets.upload_link_mailable', \App\Mail\UploadLinkMail::class);

        if (class_exists($mailableClass)) {
            Mail::to($email)->send(new $mailableClass($orderLine, $uploadUrl));

            Log::info('DeliverLaterService: Upload link email sent', [
                'order_line_id' => $orderLine->id,
                'email' => $email,
            ]);
        } else {
            Log::warning('DeliverLaterService: Mailable class not found', [
                'class' => $mailableClass,
            ]);
        }
    }

    /**
     * Mark uploads as complete for an order line.
     */
    public function markUploadsComplete(OrderLine $orderLine): void
    {
        $orderLine->update([
            'upload_completed_at' => now(),
        ]);

        Log::info('DeliverLaterService: Uploads marked complete', [
            'order_line_id' => $orderLine->id,
        ]);
    }

    /**
     * Check if uploads are complete for an order line.
     */
    public function isUploadComplete(OrderLine $orderLine): bool
    {
        return $orderLine->upload_completed_at !== null;
    }

    /**
     * Check if the upload token is expired.
     */
    public function isTokenExpired(OrderLine $orderLine): bool
    {
        $expiresAt = $orderLine->upload_token_expires_at;

        if (! $expiresAt) {
            return false;
        }

        return now()->isAfter($expiresAt);
    }

    /**
     * Get all order lines with pending uploads.
     */
    public function getPendingUploadOrderLines(): \Illuminate\Database\Eloquent\Collection
    {
        return OrderLine::where('upload_deliver_later', true)
            ->whereNull('upload_completed_at')
            ->whereNotNull('upload_token')
            ->where(function ($query) {
                $query->whereNull('upload_token_expires_at')
                    ->orWhere('upload_token_expires_at', '>', now());
            })
            ->with(['order', 'purchasable'])
            ->get();
    }
}
