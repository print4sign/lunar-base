<?php

namespace Lunar\Managers;

use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use Lunar\Base\SupplierDriverInterface;
use Lunar\Drivers\Suppliers\OfflineDriver;
use Lunar\Models\Contracts\Supplier;

class SupplierManager extends Manager
{
    /**
     * Get a supplier driver instance.
     */
    public function supplier(string|Supplier $supplier): SupplierDriverInterface
    {
        if (is_string($supplier)) {
            $supplier = \Lunar\Models\Supplier::modelClass()::where('handle', $supplier)->firstOrFail();
        }

        return $this->driver($supplier->driver)->setSupplier($supplier);
    }

    /**
     * Create the offline driver instance.
     */
    public function createOfflineDriver(): SupplierDriverInterface
    {
        return $this->buildProvider(OfflineDriver::class);
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        $originalDriver = $driver;

        $driverConfig = config("lunar.suppliers.drivers.{$driver}");

        $driverClass = $driverConfig['class'] ?? null;

        $driverInstance = null;

        // Check for custom creators first
        if (isset($this->customCreators[$driver])) {
            $driverInstance = $this->callCustomCreator($driver);
        } elseif ($driverClass && class_exists($driverClass)) {
            // Use the class from config
            $driverInstance = $this->buildProvider($driverClass);
        } else {
            // Try the conventional create{Driver}Driver method
            $method = 'create'.Str::studly($driver).'Driver';

            if (method_exists($this, $method)) {
                $driverInstance = $this->$method();
            }
        }

        if ($driverInstance) {
            return $driverInstance->setConfig($driverConfig ?? []);
        }

        return parent::createDriver($originalDriver);
    }

    /**
     * Build a driver instance.
     */
    public function buildProvider(string $provider): SupplierDriverInterface
    {
        return $this->container->make($provider);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('lunar.suppliers.default', 'offline');
    }
}
