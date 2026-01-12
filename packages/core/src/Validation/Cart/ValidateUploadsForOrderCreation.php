<?php

namespace Lunar\Validation\Cart;

use Lunar\Models\Cart;
use Lunar\Validation\BaseValidator;

/**
 * Validates that all cart lines with upload requirements
 * have either completed uploads or are marked for deliver-later.
 */
class ValidateUploadsForOrderCreation extends BaseValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate(): bool
    {
        /** @var Cart $cart */
        $cart = $this->parameters['cart'];

        foreach ($cart->lines as $line) {
            $spec = $line->resolveUploadSpec();

            // Skip lines that don't require uploads
            if (! $spec?->requiresUpload()) {
                continue;
            }

            $meta = $line->meta ?? [];
            $isDeliverLater = ($meta['upload_deliver_later'] ?? false) === true;

            // If marked for deliver-later, it's valid
            if ($isDeliverLater) {
                continue;
            }

            $uploadMethod = $meta['upload_method'] ?? null;

            // Check Probo uploader status
            if ($uploadMethod === 'probo') {
                $proboStatus = $meta['probo_uploader_status'] ?? null;

                if (! in_array($proboStatus, ['confirmed', 'processed'])) {
                    return $this->fail(
                        'cart_line_'.$line->id,
                        __('lunar::validation.cart.uploads_incomplete', [
                            'product' => $line->purchasable?->getDescription() ?? 'Unknown product',
                        ])
                    );
                }

                continue;
            }

            // Check custom upload completeness
            if (! $line->hasAllRequiredPrintAssets()) {
                return $this->fail(
                    'cart_line_'.$line->id,
                    __('lunar::validation.cart.uploads_incomplete', [
                        'product' => $line->purchasable?->getDescription() ?? 'Unknown product',
                    ])
                );
            }
        }

        return $this->pass();
    }
}
