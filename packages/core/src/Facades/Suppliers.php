<?php

namespace Lunar\Facades;

use Illuminate\Support\Facades\Facade;
use Lunar\Managers\Contracts\SupplierManagerInterface;

/**
 * @method static \Lunar\Base\SupplierDriverInterface supplier(string|\Lunar\Models\Contracts\Supplier $supplier)
 * @method static \Lunar\Base\SupplierDriverInterface createOfflineDriver()
 * @method static mixed buildProvider(string $provider)
 * @method static string getDefaultDriver()
 * @method static mixed driver(string|null $driver = null)
 * @method static \Lunar\Managers\SupplierManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \Lunar\Managers\SupplierManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Lunar\Managers\SupplierManager forgetDrivers()
 *
 * @see \Lunar\Managers\SupplierManager
 */
class Suppliers extends Facade
{
    public static function getFacadeAccessor()
    {
        return SupplierManagerInterface::class;
    }
}
