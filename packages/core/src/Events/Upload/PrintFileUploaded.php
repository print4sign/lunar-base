<?php

namespace Lunar\Events\Upload;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\CartLine;
use Lunar\Models\PrintAsset;

/**
 * Fired when a print file is uploaded for a cart line.
 */
class PrintFileUploaded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartLine $cartLine,
        public PrintAsset $printAsset,
        public int $uploaderIndex,
        public int $fileIndex,
    ) {}
}
