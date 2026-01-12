<?php

namespace Lunar\Events\Upload;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Models\CartLine;

/**
 * Fired when an upload specification is resolved for a cart line.
 */
class UploadSpecResolved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartLine $cartLine,
        public UploadSpec $uploadSpec,
        public string $source,
    ) {}
}
