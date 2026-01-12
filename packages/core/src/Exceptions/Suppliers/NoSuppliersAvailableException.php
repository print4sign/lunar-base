<?php

namespace Lunar\Exceptions\Suppliers;

use Exception;

class NoSuppliersAvailableException extends Exception
{
    protected $message = 'No suppliers available for this product variant';
}
