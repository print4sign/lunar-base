<?php

namespace Lunar\Events\Upload;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Lunar\Models\OrderLine;

/**
 * Fired when all print assets are ready for fulfillment on an order line.
 *
 * This event is dispatched after order creation when print assets
 * have been successfully copied from cart line to order line.
 */
class PrintAssetReady
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, \Lunar\Models\PrintAsset>  $printAssets
     */
    public function __construct(
        public OrderLine $orderLine,
        public Collection $printAssets,
    ) {}
}
