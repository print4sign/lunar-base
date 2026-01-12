<?php

namespace Lunar\Events\Upload;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lunar\Base\DataTransferObjects\Upload\UploadValidationResult;
use Lunar\Models\CartLine;

/**
 * Fired after print files are validated against the upload spec.
 */
class PrintFilesValidated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartLine $cartLine,
        public UploadValidationResult $result,
    ) {}
}
