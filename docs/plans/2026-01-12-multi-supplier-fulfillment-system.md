# Multi-Supplier Fulfillment System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a complete multi-supplier fulfillment system that supports Probo, HelloPrint, print.com, and internal fulfillment with AI-powered supplier matching, runtime supplier selection, order approval workflows, cancellations, refunds, and customer tracking.

**Architecture:** Extend existing Lunar Core supplier system with unified upload specs, activity tracking, payment integration, and customer-facing order tracking. Uses existing `SupplierDriverInterface`, `LogsActivity` trait, `PrintAsset` model, and `ShippingManifest` system. Add "Internal" supplier for non-supplier products to ensure all orders flow through same system.

**Tech Stack:**
- Laravel 11.x (existing)
- Filament 3.x (existing admin)
- Livewire 3.x (existing frontend)
- Spatie Activity Log (existing)
- Stripe/Mollie for payments (existing)
- OpenAI for AI matching (future phase)

---

## Phase 1: Core Infrastructure & Internal Supplier

### Task 1.1: Database Migrations for Extended Supplier Orders

**Files:**
- Create: `packages/core/database/migrations/2026_01_12_100000_extend_supplier_orders_table.php`

**Step 1: Create migration file**

```bash
cd packages/core
php artisan make:migration extend_supplier_orders_table --path=database/migrations
```

**Step 2: Write migration to extend supplier_orders table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lunar_supplier_orders', function (Blueprint $table) {
            // Cancellation fields
            $table->boolean('cancellable')->default(true)->after('status');
            $table->timestamp('cancellation_deadline')->nullable()->after('cancellable');
            $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_deadline');
            $table->foreignId('cancellation_requested_by')->nullable()->constrained('users')->after('cancellation_requested_at');
            $table->text('cancellation_reason')->nullable()->after('cancellation_requested_by');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->after('cancelled_at');
            $table->integer('cancellation_fee')->nullable()->comment('in cents')->after('cancelled_by');

            // Cost tracking
            $table->integer('estimated_cost_price')->nullable()->comment('in cents')->after('cost_price');
            $table->integer('actual_cost_price')->nullable()->comment('in cents')->after('estimated_cost_price');
            $table->integer('supplier_shipping_cost')->nullable()->comment('in cents')->after('actual_cost_price');
            $table->json('supplier_additional_costs')->nullable()->after('supplier_shipping_cost');
            $table->integer('supplier_total_cost')->nullable()->comment('in cents')->after('supplier_additional_costs');

            // Revenue tracking
            $table->integer('order_line_unit_price')->nullable()->comment('in cents')->after('supplier_total_cost');
            $table->integer('order_line_total')->nullable()->comment('in cents')->after('order_line_unit_price');
            $table->integer('profit_margin_cents')->nullable()->comment('in cents')->after('order_line_total');
            $table->decimal('profit_margin_percentage', 5, 2)->nullable()->after('profit_margin_cents');

            // Refund tracking
            $table->integer('refund_amount')->nullable()->comment('in cents')->after('profit_margin_percentage');
            $table->timestamp('refund_issued_at')->nullable()->after('refund_amount');
            $table->text('refund_reason')->nullable()->after('refund_issued_at');

            // Approval workflow
            $table->boolean('requires_approval')->default(true)->after('refund_reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('requires_approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->text('rejected_reason')->nullable()->after('approval_notes');

            // Artwork management
            $table->json('artwork_files')->nullable()->after('rejected_reason');
            $table->enum('artwork_status', ['not_required', 'pending', 'uploaded', 'approved', 'rejected'])->default('not_required')->after('artwork_files');
            $table->timestamp('artwork_approval_deadline')->nullable()->after('artwork_status');

            // Delivery tracking
            $table->date('estimated_delivery_date')->nullable()->after('artwork_approval_deadline');
            $table->date('actual_delivery_date')->nullable()->after('estimated_delivery_date');
            $table->json('tracking_numbers')->nullable()->after('actual_delivery_date');
            $table->string('tracking_url')->nullable()->after('tracking_numbers');

            // External status
            $table->string('substatus')->nullable()->after('status');
            $table->string('external_status')->nullable()->after('substatus');

            // Add indexes for common queries
            $table->index(['status', 'requires_approval']);
            $table->index(['supplier_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('cancellation_deadline');
            $table->index('estimated_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('lunar_supplier_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'requires_approval']);
            $table->dropIndex(['supplier_id', 'status']);
            $table->dropIndex(['order_id', 'status']);
            $table->dropIndex(['cancellation_deadline']);
            $table->dropIndex(['estimated_delivery_date']);

            $table->dropColumn([
                'cancellable', 'cancellation_deadline', 'cancellation_requested_at',
                'cancellation_requested_by', 'cancellation_reason', 'cancelled_at',
                'cancelled_by', 'cancellation_fee', 'estimated_cost_price',
                'actual_cost_price', 'supplier_shipping_cost', 'supplier_additional_costs',
                'supplier_total_cost', 'order_line_unit_price', 'order_line_total',
                'profit_margin_cents', 'profit_margin_percentage', 'refund_amount',
                'refund_issued_at', 'refund_reason', 'requires_approval', 'approved_by',
                'approved_at', 'approval_notes', 'rejected_reason', 'artwork_files',
                'artwork_status', 'artwork_approval_deadline', 'estimated_delivery_date',
                'actual_delivery_date', 'tracking_numbers', 'tracking_url', 'substatus',
                'external_status'
            ]);
        });
    }
};
```

**Step 3: Create migration for print_assets extension**

Create: `packages/core/database/migrations/2026_01_12_100001_extend_print_assets_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lunar_print_assets', function (Blueprint $table) {
            $table->foreignId('supplier_order_id')->nullable()->constrained('lunar_supplier_orders')->after('order_line_id');
            $table->string('supplier_file_id')->nullable()->comment('External file ID from supplier')->after('supplier_order_id');
            $table->string('supplier_status')->nullable()->comment('File status at supplier')->after('supplier_file_id');
            $table->json('validation_errors')->nullable()->comment('Supplier validation feedback')->after('supplier_status');

            $table->index('supplier_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('lunar_print_assets', function (Blueprint $table) {
            $table->dropIndex(['supplier_order_id']);
            $table->dropColumn(['supplier_order_id', 'supplier_file_id', 'supplier_status', 'validation_errors']);
        });
    }
};
```

**Step 4: Run migrations**

```bash
cd /Users/beaudinngreve/Code/personal/print4sign/lunar-base
php artisan migrate --path=packages/core/database/migrations/2026_01_12_100000_extend_supplier_orders_table.php
php artisan migrate --path=packages/core/database/migrations/2026_01_12_100001_extend_print_assets_table.php
```

**Step 5: Commit**

```bash
git add packages/core/database/migrations/
git commit -m "feat(core): extend supplier_orders and print_assets tables for multi-supplier system"
```

---

### Task 1.2: Internal Supplier Driver

**Files:**
- Create: `packages/core/src/Drivers/Suppliers/InternalDriver.php`
- Modify: `packages/core/config/suppliers.php`

**Step 1: Create InternalDriver class**

```php
<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Models\Contracts\SupplierOrder;

/**
 * Internal Supplier Driver
 *
 * This driver handles products that are fulfilled internally
 * (i.e., not through an external supplier). It provides minimal
 * implementation for the SupplierDriverInterface.
 */
class InternalDriver extends AbstractSupplierDriver
{
    /**
     * Get the capabilities of this driver.
     */
    public function capabilities(): array
    {
        return [
            'ordering',     // Can create internal orders
            'tracking',     // Manual tracking updates
        ];
    }

    /**
     * Sync catalog - not applicable for internal products.
     */
    public function syncCatalog(): Collection
    {
        return collect();
    }

    /**
     * Get product - not applicable for internal products.
     */
    public function getProduct(string $externalId): ?array
    {
        return null;
    }

    /**
     * Get configurator schema - not applicable.
     */
    public function getConfiguratorSchema(string $externalId): ?array
    {
        return null;
    }

    /**
     * Configure product - not applicable.
     */
    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        return ConfiguratorResponse::fromArray([
            'external_id' => $externalId,
            'available_options' => [],
            'selected_options' => $selections,
            'can_order' => true,
        ]);
    }

    /**
     * Get price - returns the product's configured price.
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        // For internal products, price comes from the product variant
        // This is a fallback that returns zero - actual pricing happens
        // through the product variant's unit_price

        return new PriceResponse(
            costPrice: 0,
            sellPrice: 0,
            currency: config('lunar.default_currency', 'EUR'),
        );
    }

    /**
     * Get bulk prices - returns empty collection.
     */
    public function getBulkPrices(array $items): Collection
    {
        return collect();
    }

    /**
     * Get price with shipping - not applicable.
     */
    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        return $this->getPrice($externalId, $configuration);
    }

    /**
     * Submit order - creates internal order record.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        // For internal orders, we just mark as submitted
        // Actual fulfillment is done manually

        $externalId = 'INTERNAL-' . $supplierOrder->order->reference . '-' . $supplierOrder->order_line_id;

        return OrderResponse::success(
            externalId: $externalId,
            status: 'submitted',
            data: [
                'type' => 'internal',
                'submitted_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Get order status - returns current status.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        return new StatusResponse(
            status: 'processing',
            externalId: $externalOrderId,
            data: ['type' => 'internal']
        );
    }

    /**
     * Cancel order - marks as cancelled.
     */
    public function cancelOrder(string $externalOrderId): bool
    {
        // Internal orders can always be cancelled
        return true;
    }

    /**
     * Get hidden sections for dynamic products - none for internal.
     */
    public function getHiddenSectionsForDynamic(): array
    {
        return [];
    }

    /**
     * Get configurator component - none for internal.
     */
    public function getConfiguratorComponent(): ?string
    {
        return null;
    }

    /**
     * Get dynamic price - not applicable.
     */
    public function getDynamicPrice(string $externalId, array $configuration, int $quantity): PriceResponse
    {
        return $this->getPrice($externalId, $configuration);
    }

    /**
     * Get cart line pipelines - none for internal.
     */
    public function getCartLinePipelines(): array
    {
        return [];
    }
}
```

**Step 2: Register InternalDriver in config**

Modify: `packages/core/config/suppliers.php` (create if doesn't exist)

```php
<?php

return [
    'default' => env('LUNAR_DEFAULT_SUPPLIER', 'internal'),

    'drivers' => [
        'internal' => [
            'class' => \Lunar\Drivers\Suppliers\InternalDriver::class,
        ],

        'offline' => [
            'class' => \Lunar\Drivers\Suppliers\OfflineDriver::class,
        ],

        'probo' => [
            'class' => \Lunar\Drivers\Suppliers\ProboDriver::class,
            'api_key' => env('PROBO_API_KEY'),
            'sandbox' => env('PROBO_SANDBOX', false),
        ],
    ],
];
```

**Step 3: Create seeder for Internal supplier**

Create: `packages/core/database/seeders/InternalSupplierSeeder.php`

```php
<?php

namespace Lunar\Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Supplier;

class InternalSupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::firstOrCreate(
            ['handle' => 'internal'],
            [
                'name' => 'Internal Fulfillment',
                'icon' => 'building-storefront',
                'driver' => 'internal',
                'credentials' => [],
                'capabilities' => ['ordering', 'tracking'],
                'enabled' => true,
                'priority' => 0, // Lowest priority - only use if no other supplier
                'meta' => [
                    'description' => 'Products fulfilled internally by our warehouse',
                    'cancellation_window' => 72, // 72 hours
                    'cancellation_allowed_statuses' => ['pending', 'approved', 'submitted', 'processing'],
                ],
            ]
        );
    }
}
```

**Step 4: Run seeder**

```bash
php artisan db:seed --class=Lunar\\Database\\Seeders\\InternalSupplierSeeder
```

**Step 5: Commit**

```bash
git add packages/core/src/Drivers/Suppliers/InternalDriver.php
git add packages/core/config/suppliers.php
git add packages/core/database/seeders/InternalSupplierSeeder.php
git commit -m "feat(core): add Internal supplier driver for non-supplier products"
```

---

## Phase 2: HelloPrint & print.com Drivers

### Task 2.1: HelloPrint Driver Implementation

**Files:**
- Create: `packages/core/src/Drivers/Suppliers/HelloPrintDriver.php`
- Create: `packages/core/src/Drivers/Suppliers/HelloPrint/Pipelines/GetHelloPrintPrice.php`

**Step 1: Create HelloPrintDriver class**

```php
<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Lunar\Base\Contracts\ProvidesUploadSpec;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\ShippingOptionResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Base\DataTransferObjects\Upload\UploaderRequirement;
use Lunar\Drivers\Suppliers\HelloPrint\Pipelines\GetHelloPrintPrice;
use Lunar\Exceptions\Suppliers\SupplierException;
use Lunar\Models\Contracts\SupplierOrder;
use Lunar\Models\SupplierProduct;

class HelloPrintDriver extends AbstractSupplierDriver implements ProvidesUploadSpec
{
    protected string $baseUrl = 'https://api.helloprint.com/rest/v1/';

    public function capabilities(): array
    {
        return [
            'catalog_sync',
            'pricing',
            'ordering',
            'file_upload',
            'tracking',
        ];
    }

    public function getCartLinePipelines(): array
    {
        return [
            GetHelloPrintPrice::class,
        ];
    }

    protected function http(): PendingRequest
    {
        $apiKey = $this->getCredential('api_key');

        return parent::http()
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ]);
    }

    public function syncCatalog(): Collection
    {
        $allProducts = collect();
        $page = 1;

        while (true) {
            $response = $this->http()->get('products', [
                'page' => $page,
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                throw new SupplierException('Failed to fetch HelloPrint catalog: ' . $response->body());
            }

            $data = $response->json();
            $products = $data['data'] ?? [];

            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'external_id' => $product['sku'],
                    ],
                    [
                        'external_name' => $product['name'] ?? $product['sku'],
                        'external_data' => $product,
                        'active' => $product['active'] ?? true,
                        'synced' => true,
                        'last_synced_at' => now(),
                    ]
                );

                $allProducts->push($supplierProduct);
            }

            $page++;

            // Check if there are more pages
            if (!isset($data['pagination']['next'])) {
                break;
            }
        }

        return $allProducts;
    }

    public function getProduct(string $externalId): ?array
    {
        $response = $this->http()->get("products/{$externalId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function getConfiguratorSchema(string $externalId): ?array
    {
        $response = $this->http()->get("products/{$externalId}/variants");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        // HelloPrint doesn't have iterative configuration
        // Variants are predefined with variantKey

        return ConfiguratorResponse::fromArray([
            'external_id' => $externalId,
            'available_options' => [],
            'selected_options' => $selections,
            'can_order' => isset($selections['variantKey']),
        ]);
    }

    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        // HelloPrint uses /quotes endpoint for pricing
        $response = $this->http()->post('quotes', [
            'products' => [
                [
                    'sku' => $externalId,
                    'variantKey' => $configuration['variantKey'] ?? null,
                    'quantity' => $configuration['quantity'] ?? 1,
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new SupplierException('Failed to get price from HelloPrint: ' . $response->body());
        }

        $data = $response->json();
        $product = $data['products'][0] ?? null;

        if (!$product) {
            throw new SupplierException('No price data returned from HelloPrint');
        }

        // HelloPrint returns prices in cents
        return new PriceResponse(
            costPrice: (int) ($product['costPrice'] ?? 0),
            sellPrice: (int) ($product['sellPrice'] ?? 0),
            currency: $product['currency'] ?? 'EUR',
            breakdown: [
                'base_price' => $product['basePrice'] ?? null,
                'setup_cost' => $product['setupCost'] ?? null,
            ],
            meta: ['raw_response' => $data],
        );
    }

    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        $priceResponse = $this->getPrice($externalId, $configuration);

        // TODO: Add shipping calculation once HelloPrint API supports it

        return $priceResponse;
    }

    public function getBulkPrices(array $items): Collection
    {
        $products = collect($items)->map(function ($item) {
            return [
                'sku' => $item['external_id'],
                'variantKey' => $item['configuration']['variantKey'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
            ];
        })->values()->all();

        $response = $this->http()->post('quotes', [
            'products' => $products,
        ]);

        if (!$response->successful()) {
            throw new SupplierException('Failed to get bulk prices from HelloPrint: ' . $response->body());
        }

        $data = $response->json();

        return collect($data['products'] ?? [])->map(function ($product) {
            return new PriceResponse(
                costPrice: (int) ($product['costPrice'] ?? 0),
                sellPrice: (int) ($product['sellPrice'] ?? 0),
                currency: $product['currency'] ?? 'EUR',
            );
        });
    }

    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        $orderLine = $supplierOrder->orderLine;
        $variant = $orderLine->purchasable;
        $order = $supplierOrder->order;
        $shippingAddress = $order->shippingAddress;

        $orderData = [
            'mode' => $this->getConfig('sandbox', false) ? 'test' : 'live',
            'customerReference' => $order->reference . '-' . $orderLine->id,
            'products' => [
                [
                    'sku' => $variant->supplierProduct?->external_id,
                    'variantKey' => $variant->configuration['variantKey'] ?? null,
                    'quantity' => $orderLine->quantity,
                    'artworkFile' => $this->getArtworkUrl($supplierOrder),
                ],
            ],
            'deliveryAddress' => [
                'name' => $shippingAddress?->fullName ?? $shippingAddress?->company_name,
                'street' => $shippingAddress?->line_one,
                'postalCode' => $shippingAddress?->postcode,
                'city' => $shippingAddress?->city,
                'country' => $shippingAddress?->country?->iso2,
                'phone' => $shippingAddress?->contact_phone,
                'email' => $shippingAddress?->contact_email,
            ],
        ];

        $response = $this->http()->post('orders', $orderData);

        if (!$response->successful()) {
            return OrderResponse::failed(
                'Failed to submit order to HelloPrint: ' . $response->body(),
                ['response' => $response->json()]
            );
        }

        $data = $response->json();

        return OrderResponse::success(
            externalId: $data['orderId'] ?? uniqid('hp-'),
            status: $data['status'] ?? 'submitted',
            data: $data
        );
    }

    protected function getArtworkUrl(SupplierOrder $supplierOrder): ?string
    {
        $printAsset = $supplierOrder->orderLine->printAssets()->first();

        if (!$printAsset) {
            return null;
        }

        return $printAsset->getTemporaryUrl(60);
    }

    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        $response = $this->http()->get("orders/{$externalOrderId}");

        if (!$response->successful()) {
            throw new SupplierException('Failed to get order status from HelloPrint: ' . $response->body());
        }

        $data = $response->json();

        return new StatusResponse(
            status: $this->mapHelloPrintStatus($data['status'] ?? 'unknown'),
            externalId: $externalOrderId,
            tracking: $this->extractTracking($data),
            data: $data
        );
    }

    protected function mapHelloPrintStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending', 'order_created' => 'pending',
            'artwork_received', 'artwork_accepted', 'in_production' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'error' => 'failed',
            default => $status,
        };
    }

    protected function extractTracking(array $data): ?array
    {
        if (!isset($data['tracking'])) {
            return null;
        }

        return [
            'numbers' => [$data['tracking']['trackingNumber'] ?? null],
            'url' => $data['tracking']['trackingUrl'] ?? null,
        ];
    }

    public function cancelOrder(string $externalOrderId): bool
    {
        $response = $this->http()->post("orders/{$externalOrderId}/cancel");

        return $response->successful();
    }

    public function getHiddenSectionsForDynamic(): array
    {
        return [];
    }

    public function getConfiguratorComponent(): ?string
    {
        return null;
    }

    public function getDynamicPrice(string $externalId, array $configuration, int $quantity): PriceResponse
    {
        $config = array_merge($configuration, ['quantity' => $quantity]);
        return $this->getPrice($externalId, $config);
    }

    public function resolveUploadSpec(string $externalId, array $configuration): ?UploadSpec
    {
        $response = $this->http()->get("products/{$externalId}/variants/{$configuration['variantKey']}");

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!($data['requiresArtwork'] ?? false)) {
            return UploadSpec::empty();
        }

        $specs = $data['artworkSpecs'] ?? [];
        $uploaders = [];

        if (($specs['sides'] ?? 'single') === 'double') {
            $uploaders[] = UploaderRequirement::fromArray([
                'type' => 'frontback',
                'amount' => 2,
                'width' => $specs['dimensions']['width'] ?? null,
                'height' => $specs['dimensions']['height'] ?? null,
                'unit' => $specs['dimensions']['unit'] ?? 'mm',
                'accepted_formats' => $specs['acceptedFileTypes'] ?? ['pdf', 'jpg', 'png'],
                'max_file_size' => ($specs['maxFileSizeMb'] ?? 50) * 1024 * 1024,
            ]);
        } else {
            $uploaders[] = UploaderRequirement::fromArray([
                'type' => 'single',
                'amount' => 1,
                'width' => $specs['dimensions']['width'] ?? null,
                'height' => $specs['dimensions']['height'] ?? null,
                'unit' => $specs['dimensions']['unit'] ?? 'mm',
                'accepted_formats' => $specs['acceptedFileTypes'] ?? ['pdf', 'jpg', 'png'],
                'max_file_size' => ($specs['maxFileSizeMb'] ?? 50) * 1024 * 1024,
            ]);
        }

        return UploadSpec::fromArray([
            'upload' => true,
            'uploaders' => $uploaders,
            'version' => UploadSpec::VERSION,
            'source' => UploadSpec::SOURCE_SUPPLIER . ':helloprint',
        ]);
    }
}
```

**Step 2: Create HelloPrint pricing pipeline**

Create: `packages/core/src/Drivers/Suppliers/HelloPrint/Pipelines/GetHelloPrintPrice.php`

```php
<?php

namespace Lunar\Drivers\Suppliers\HelloPrint\Pipelines;

use Closure;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Managers\SupplierManager;
use Lunar\Models\CartLine;

class GetHelloPrintPrice
{
    public function __construct(
        protected SupplierManager $suppliers
    ) {}

    public function handle(CartLine $cartLine, Closure $next): CartLine
    {
        $variant = $cartLine->purchasable;

        // Only run if variant has HelloPrint supplier product
        if (!$variant->supplierProduct ||
            $variant->supplierProduct->supplier->driver !== 'helloprint') {
            return $next($cartLine);
        }

        // Only run for dynamic variants
        if (!$variant->is_dynamic) {
            return $next($cartLine);
        }

        try {
            $driver = $this->suppliers->supplier($variant->supplierProduct->supplier);

            $priceResponse = $driver->getDynamicPrice(
                $variant->supplierProduct->external_id,
                $variant->configuration ?? [],
                $cartLine->quantity
            );

            // Update cart line with HelloPrint price
            $cartLine->unit_price = $priceResponse->sellPrice;
            $cartLine->meta['supplier_pricing'] = [
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $priceResponse->sellPrice,
                'currency' => $priceResponse->currency,
                'fetched_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to get HelloPrint price', [
                'cart_line_id' => $cartLine->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($cartLine);
    }
}
```

**Step 3: Register HelloPrint in config**

Modify: `packages/core/config/suppliers.php`

```php
'helloprint' => [
    'class' => \Lunar\Drivers\Suppliers\HelloPrintDriver::class,
    'api_key' => env('HELLOPRINT_API_KEY'),
    'sandbox' => env('HELLOPRINT_SANDBOX', false),
],
```

**Step 4: Commit**

```bash
git add packages/core/src/Drivers/Suppliers/HelloPrintDriver.php
git add packages/core/src/Drivers/Suppliers/HelloPrint/
git add packages/core/config/suppliers.php
git commit -m "feat(core): add HelloPrint supplier driver with pricing pipeline"
```

---

### Task 2.2: print.com Driver Implementation

**Files:**
- Create: `packages/core/src/Drivers/Suppliers/PrintComDriver.php`
- Create: `packages/core/src/Drivers/Suppliers/PrintCom/Pipelines/GetPrintComPrice.php`

**Step 1: Create PrintComDriver class**

```php
<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Lunar\Base\Contracts\ProvidesUploadSpec;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Base\DataTransferObjects\Upload\UploaderRequirement;
use Lunar\Drivers\Suppliers\PrintCom\Pipelines\GetPrintComPrice;
use Lunar\Exceptions\Suppliers\SupplierException;
use Lunar\Models\Contracts\SupplierOrder;
use Lunar\Models\SupplierProduct;

class PrintComDriver extends AbstractSupplierDriver implements ProvidesUploadSpec
{
    protected string $apiUrl = 'https://api.print.com/v1/';
    protected string $platformUrl = 'https://platform.print.com/';

    public function capabilities(): array
    {
        return [
            'catalog_sync',
            'pricing',
            'ordering',
            'file_upload',
            'tracking',
            'pdf_processing',
            'batch_operations',
        ];
    }

    public function getCartLinePipelines(): array
    {
        return [
            GetPrintComPrice::class,
        ];
    }

    protected function http(): PendingRequest
    {
        $apiKey = $this->getCredential('api_key');

        return parent::http()
            ->baseUrl($this->apiUrl)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ]);
    }

    public function syncCatalog(): Collection
    {
        $allProducts = collect();
        $offset = 0;
        $limit = 100;

        while (true) {
            $response = $this->http()->get('products', [
                'offset' => $offset,
                'limit' => $limit,
            ]);

            if (!$response->successful()) {
                throw new SupplierException('Failed to fetch print.com catalog: ' . $response->body());
            }

            $data = $response->json();
            $products = $data['data'] ?? [];

            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'external_id' => $product['sku'],
                    ],
                    [
                        'external_name' => $product['name'] ?? $product['sku'],
                        'external_data' => $product,
                        'active' => $product['active'] ?? true,
                        'synced' => true,
                        'last_synced_at' => now(),
                    ]
                );

                $allProducts->push($supplierProduct);
            }

            $offset += $limit;

            // Check if there are more items
            if (count($products) < $limit) {
                break;
            }
        }

        return $allProducts;
    }

    public function getProduct(string $externalId): ?array
    {
        $response = $this->http()->get("products/{$externalId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function getConfiguratorSchema(string $externalId): ?array
    {
        $response = $this->http()->get("products/{$externalId}/options");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        return ConfiguratorResponse::fromArray([
            'external_id' => $externalId,
            'available_options' => [],
            'selected_options' => $selections,
            'can_order' => isset($selections['sku']),
        ]);
    }

    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        $response = $this->http()->post('products/price', [
            'products' => [
                [
                    'sku' => $externalId,
                    'quantity' => $configuration['quantity'] ?? 1,
                    'options' => $configuration['options'] ?? [],
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new SupplierException('Failed to get price from print.com: ' . $response->body());
        }

        $data = $response->json();
        $product = $data['products'][0] ?? null;

        if (!$product) {
            throw new SupplierException('No price data returned from print.com');
        }

        // print.com returns prices in cents
        return new PriceResponse(
            costPrice: (int) ($product['cost'] ?? 0),
            sellPrice: (int) ($product['price'] ?? 0),
            currency: $product['currency'] ?? 'USD',
            breakdown: [
                'base_price' => $product['basePrice'] ?? null,
                'options_price' => $product['optionsPrice'] ?? null,
            ],
            meta: ['raw_response' => $data],
        );
    }

    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        $priceResponse = $this->getPrice($externalId, $configuration);

        // TODO: Add shipping calculation

        return $priceResponse;
    }

    public function getBulkPrices(array $items): Collection
    {
        $products = collect($items)->map(function ($item) {
            return [
                'sku' => $item['external_id'],
                'quantity' => $item['quantity'] ?? 1,
                'options' => $item['configuration']['options'] ?? [],
            ];
        })->values()->all();

        $response = $this->http()->post('products/price', [
            'products' => $products,
        ]);

        if (!$response->successful()) {
            throw new SupplierException('Failed to get bulk prices from print.com: ' . $response->body());
        }

        $data = $response->json();

        return collect($data['products'] ?? [])->map(function ($product) {
            return new PriceResponse(
                costPrice: (int) ($product['cost'] ?? 0),
                sellPrice: (int) ($product['price'] ?? 0),
                currency: $product['currency'] ?? 'USD',
            );
        });
    }

    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        $orderLine = $supplierOrder->orderLine;
        $variant = $orderLine->purchasable;
        $order = $supplierOrder->order;
        $shippingAddress = $order->shippingAddress;

        $orderData = [
            'reference' => $order->reference . '-' . $orderLine->id,
            'items' => [
                [
                    'sku' => $variant->supplierProduct?->external_id,
                    'quantity' => $orderLine->quantity,
                    'options' => $variant->configuration['options'] ?? [],
                    'artwork' => [
                        'url' => $this->getArtworkUrl($supplierOrder),
                    ],
                ],
            ],
            'shippingAddress' => [
                'name' => $shippingAddress?->fullName ?? $shippingAddress?->company_name,
                'street1' => $shippingAddress?->line_one,
                'street2' => $shippingAddress?->line_two,
                'city' => $shippingAddress?->city,
                'postalCode' => $shippingAddress?->postcode,
                'country' => $shippingAddress?->country?->iso2,
                'phone' => $shippingAddress?->contact_phone,
                'email' => $shippingAddress?->contact_email,
            ],
        ];

        $response = $this->http()->post('orders', $orderData);

        if (!$response->successful()) {
            return OrderResponse::failed(
                'Failed to submit order to print.com: ' . $response->body(),
                ['response' => $response->json()]
            );
        }

        $data = $response->json();

        return OrderResponse::success(
            externalId: $data['id'] ?? uniqid('pc-'),
            status: $data['status'] ?? 'submitted',
            data: $data
        );
    }

    protected function getArtworkUrl(SupplierOrder $supplierOrder): ?string
    {
        $printAsset = $supplierOrder->orderLine->printAssets()->first();

        if (!$printAsset) {
            return null;
        }

        return $printAsset->getTemporaryUrl(60);
    }

    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        $response = $this->http()->get("orders/{$externalOrderId}");

        if (!$response->successful()) {
            throw new SupplierException('Failed to get order status from print.com: ' . $response->body());
        }

        $data = $response->json();

        return new StatusResponse(
            status: $this->mapPrintComStatus($data['status'] ?? 'unknown'),
            externalId: $externalOrderId,
            tracking: $this->extractTracking($data),
            data: $data
        );
    }

    protected function mapPrintComStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending', 'pending_artwork_approval' => 'pending',
            'artwork_approved', 'ready_for_production', 'in_production' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'failed' => 'failed',
            default => $status,
        };
    }

    protected function extractTracking(array $data): ?array
    {
        if (!isset($data['shipments'])) {
            return null;
        }

        $trackingNumbers = [];
        $trackingUrl = null;

        foreach ($data['shipments'] as $shipment) {
            if (isset($shipment['trackingNumber'])) {
                $trackingNumbers[] = $shipment['trackingNumber'];
            }
            if (isset($shipment['trackingUrl']) && !$trackingUrl) {
                $trackingUrl = $shipment['trackingUrl'];
            }
        }

        if (empty($trackingNumbers)) {
            return null;
        }

        return [
            'numbers' => $trackingNumbers,
            'url' => $trackingUrl,
        ];
    }

    public function cancelOrder(string $externalOrderId): bool
    {
        $response = $this->http()->delete("orders/{$externalOrderId}");

        return $response->successful();
    }

    public function getHiddenSectionsForDynamic(): array
    {
        return [];
    }

    public function getConfiguratorComponent(): ?string
    {
        return null;
    }

    public function getDynamicPrice(string $externalId, array $configuration, int $quantity): PriceResponse
    {
        $config = array_merge($configuration, ['quantity' => $quantity]);
        return $this->getPrice($externalId, $config);
    }

    public function resolveUploadSpec(string $externalId, array $configuration): ?UploadSpec
    {
        $response = $this->http()->get("products/{$externalId}/specs");

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $artwork = $data['artwork'] ?? [];

        if (!($artwork['required'] ?? false)) {
            return UploadSpec::empty();
        }

        $uploaders = [];
        $pages = $artwork['pages'] ?? 1;

        for ($i = 0; $i < $pages; $i++) {
            $uploaders[] = UploaderRequirement::fromArray([
                'type' => 'single',
                'amount' => 1,
                'label' => $pages > 1 ? "Page " . ($i + 1) : null,
                'width' => $artwork['dimensions']['width'] ?? null,
                'height' => $artwork['dimensions']['height'] ?? null,
                'unit' => $artwork['dimensions']['unit'] ?? 'mm',
                'accepted_formats' => $artwork['acceptedFormats'] ?? ['pdf'],
                'max_file_size' => ($artwork['maxSizeMb'] ?? 50) * 1024 * 1024,
                'min_dpi' => $artwork['minDpi'] ?? 300,
                'color_mode' => $artwork['colorMode'] ?? 'CMYK',
            ]);
        }

        return UploadSpec::fromArray([
            'upload' => true,
            'uploaders' => $uploaders,
            'version' => UploadSpec::VERSION,
            'source' => UploadSpec::SOURCE_SUPPLIER . ':printcom',
        ]);
    }
}
```

**Step 2: Create print.com pricing pipeline**

Create: `packages/core/src/Drivers/Suppliers/PrintCom/Pipelines/GetPrintComPrice.php`

```php
<?php

namespace Lunar\Drivers\Suppliers\PrintCom\Pipelines;

use Closure;
use Lunar\Managers\SupplierManager;
use Lunar\Models\CartLine;

class GetPrintComPrice
{
    public function __construct(
        protected SupplierManager $suppliers
    ) {}

    public function handle(CartLine $cartLine, Closure $next): CartLine
    {
        $variant = $cartLine->purchasable;

        if (!$variant->supplierProduct ||
            $variant->supplierProduct->supplier->driver !== 'printcom') {
            return $next($cartLine);
        }

        if (!$variant->is_dynamic) {
            return $next($cartLine);
        }

        try {
            $driver = $this->suppliers->supplier($variant->supplierProduct->supplier);

            $priceResponse = $driver->getDynamicPrice(
                $variant->supplierProduct->external_id,
                $variant->configuration ?? [],
                $cartLine->quantity
            );

            $cartLine->unit_price = $priceResponse->sellPrice;
            $cartLine->meta['supplier_pricing'] = [
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $priceResponse->sellPrice,
                'currency' => $priceResponse->currency,
                'fetched_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to get print.com price', [
                'cart_line_id' => $cartLine->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($cartLine);
    }
}
```

**Step 3: Register print.com in config**

Modify: `packages/core/config/suppliers.php`

```php
'printcom' => [
    'class' => \Lunar\Drivers\Suppliers\PrintComDriver::class,
    'api_key' => env('PRINTCOM_API_KEY'),
],
```

**Step 4: Commit**

```bash
git add packages/core/src/Drivers/Suppliers/PrintComDriver.php
git add packages/core/src/Drivers/Suppliers/PrintCom/
git add packages/core/config/suppliers.php
git commit -m "feat(core): add print.com supplier driver with pricing pipeline"
```

---

## Phase 3: Runtime Supplier Selection

### Task 3.1: Supplier Selection Service

**Files:**
- Create: `packages/core/src/Services/RuntimeSupplierSelector.php`
- Create: `packages/core/src/DataTransferObjects/SupplierSelectionResult.php`
- Create: `packages/core/src/Exceptions/Suppliers/NoSuppliersAvailableException.php`

**Step 1: Create SupplierSelectionResult DTO**

```php
<?php

namespace Lunar\DataTransferObjects;

use Illuminate\Support\Collection;

class SupplierSelectionResult
{
    public function __construct(
        public readonly array $primary,
        public readonly Collection $fallbacks,
        public readonly Collection $allQuotes
    ) {}

    public function getReason(): string
    {
        $factors = [];

        if (isset($this->primary['score'])) {
            $factors[] = "Score: {$this->primary['score']}/100";
        }

        if (isset($this->primary['quote']['cost_price'])) {
            $price = $this->primary['quote']['cost_price'] / 100;
            $factors[] = "Cost: €{$price}";
        }

        return implode(', ', $factors);
    }
}
```

**Step 2: Create NoSuppliersAvailableException**

```php
<?php

namespace Lunar\Exceptions\Suppliers;

use Exception;

class NoSuppliersAvailableException extends Exception
{
    protected $message = 'No suppliers available for this product variant';
}
```

**Step 3: Create RuntimeSupplierSelector service**

```php
<?php

namespace Lunar\Services;

use Illuminate\Support\Collection;
use Lunar\DataTransferObjects\SupplierSelectionResult;
use Lunar\Exceptions\Suppliers\NoSuppliersAvailableException;
use Lunar\Managers\SupplierManager;
use Lunar\Models\Contracts\Address;
use Lunar\Models\Contracts\Order;
use Lunar\Models\Contracts\OrderLine;
use Lunar\Models\Contracts\ProductVariant;

class RuntimeSupplierSelector
{
    public function __construct(
        protected SupplierManager $suppliers
    ) {}

    /**
     * Select the best supplier for an order line at order placement time
     */
    public function selectBestSupplier(
        OrderLine $orderLine,
        Order $order,
        array $criteria = []
    ): SupplierSelectionResult {

        $variant = $orderLine->purchasable;
        $quantity = $orderLine->quantity;
        $deliveryAddress = $order->shippingAddress;

        // Get all supplier options for this variant
        $supplierOptions = $this->getAvailableSuppliers($variant);

        if ($supplierOptions->isEmpty()) {
            throw new NoSuppliersAvailableException();
        }

        // Fetch real-time data from ALL suppliers in parallel
        $supplierQuotes = $this->fetchRealTimeQuotes(
            $supplierOptions,
            $variant,
            $quantity,
            $deliveryAddress
        );

        // Score each supplier based on criteria
        $scoredSuppliers = $supplierQuotes->map(function($quote) use ($criteria, $order) {
            return [
                'supplier' => $quote['supplier'],
                'supplier_product' => $quote['supplier_product'],
                'quote' => $quote,
                'score' => $this->calculateSupplierScore($quote, $criteria, $order),
            ];
        })->sortByDesc('score')->values();

        if ($scoredSuppliers->isEmpty()) {
            throw new NoSuppliersAvailableException();
        }

        return new SupplierSelectionResult(
            primary: $scoredSuppliers->first(),
            fallbacks: $scoredSuppliers->skip(1)->take(2),
            allQuotes: $scoredSuppliers
        );
    }

    /**
     * Get available suppliers for a variant
     */
    protected function getAvailableSuppliers(ProductVariant $variant): Collection
    {
        // If variant has a specific supplier product, use that
        if ($variant->supplier_product_id) {
            return collect([$variant->supplierProduct])
                ->filter(fn($sp) => $sp && $sp->active && $sp->supplier->enabled);
        }

        // Otherwise, get all supplier products for this variant
        return $variant->supplierProducts()
            ->whereHas('supplier', fn($q) => $q->where('enabled', true))
            ->where('active', true)
            ->get();
    }

    /**
     * Fetch real-time quotes from multiple suppliers
     */
    protected function fetchRealTimeQuotes(
        Collection $supplierOptions,
        ProductVariant $variant,
        int $quantity,
        ?Address $address
    ): Collection {

        $quotes = collect();

        foreach ($supplierOptions as $supplierProduct) {
            try {
                $driver = $this->suppliers->supplier($supplierProduct->supplier);

                $priceResponse = $driver->getPriceWithShipping(
                    $supplierProduct->external_id,
                    $variant->configuration ?? [],
                    $address?->toArray()
                );

                $quotes->push([
                    'supplier_product' => $supplierProduct,
                    'supplier' => $supplierProduct->supplier,
                    'available' => true,
                    'cost_price' => $priceResponse->costPrice,
                    'sell_price' => $priceResponse->sellPrice,
                    'shipping_options' => $priceResponse->meta['shipping_options'] ?? [],
                    'lead_time' => $priceResponse->breakdown['production_hours'] ?? null,
                    'fetched_at' => now(),
                ]);

            } catch (\Exception $e) {
                \Log::warning('Failed to get quote from supplier', [
                    'supplier_product_id' => $supplierProduct->id,
                    'supplier' => $supplierProduct->supplier->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $quotes;
    }

    /**
     * Calculate supplier score based on weighted criteria
     */
    protected function calculateSupplierScore(
        array $quote,
        array $criteria,
        Order $order
    ): float {

        $weights = $criteria['weights'] ?? [
            'price' => 40,
            'lead_time' => 25,
            'priority' => 20,
            'shipping_cost' => 15,
        ];

        $scores = [];

        // Price score (lower is better, normalize to 0-100)
        $scores['price'] = $this->calculatePriceScore(
            $quote['cost_price'],
            $criteria['price_range'] ?? []
        );

        // Lead time score (faster is better)
        $scores['lead_time'] = $this->calculateLeadTimeScore(
            $quote['lead_time'] ?? 96
        );

        // Priority score (from supplier product configuration)
        $scores['priority'] = ($quote['supplier_product']->priority ?? 50);

        // Shipping cost score
        $scores['shipping_cost'] = $this->calculateShippingScore(
            $quote['shipping_options'] ?? []
        );

        // Calculate weighted total
        $totalScore = 0;
        foreach ($weights as $criterion => $weight) {
            $totalScore += ($scores[$criterion] ?? 0) * ($weight / 100);
        }

        return round($totalScore, 2);
    }

    protected function calculatePriceScore(int $costPrice, array $range = []): float
    {
        // If we have a range, score relative to min/max
        if (isset($range['min']) && isset($range['max'])) {
            $normalized = ($range['max'] - $costPrice) / ($range['max'] - $range['min']);
            return max(0, min(100, $normalized * 100));
        }

        // Otherwise, just use inverse of price (lower is better)
        return max(0, 100 - ($costPrice / 1000));
    }

    protected function calculateLeadTimeScore(int $hours): float
    {
        // 24h = 100, 168h (week) = 50, 336h (2 weeks) = 0
        return max(0, 100 - (($hours - 24) / 312 * 100));
    }

    protected function calculateShippingScore(array $options): float
    {
        if (empty($options)) {
            return 50;
        }

        // Find cheapest shipping option
        $cheapest = collect($options)->min('priceInCents');

        // €0 = 100, €20 = 0
        return max(0, 100 - ($cheapest / 20));
    }
}
```

**Step 4: Commit**

```bash
git add packages/core/src/Services/RuntimeSupplierSelector.php
git add packages/core/src/DataTransferObjects/SupplierSelectionResult.php
git add packages/core/src/Exceptions/Suppliers/NoSuppliersAvailableException.php
git commit -m "feat(core): add runtime supplier selection service"
```

---

### Task 3.2: Integrate Supplier Selection into Order Creation

**Files:**
- Modify: `packages/core/src/Pipelines/Order/Creation/CreateOrderLines.php`

**Step 1: Update CreateOrderLines to use RuntimeSupplierSelector**

```php
<?php

namespace Lunar\Pipelines\Order\Creation;

use Closure;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\SupplierOrder;
use Lunar\Services\RuntimeSupplierSelector;

class CreateOrderLines
{
    public function __construct(
        protected RuntimeSupplierSelector $supplierSelector
    ) {}

    public function handle(Order $order, Closure $next): Order
    {
        foreach ($order->cart->lines as $cartLine) {
            $variant = $cartLine->purchasable;

            // Create order line
            $orderLine = OrderLine::create([
                'order_id' => $order->id,
                'purchasable_type' => get_class($variant),
                'purchasable_id' => $variant->id,
                'quantity' => $cartLine->quantity,
                'unit_price' => $cartLine->unit_price,
                'sub_total' => $cartLine->sub_total,
                'total' => $cartLine->total,
                'meta' => $cartLine->meta,
                'notes' => $cartLine->notes,
            ]);

            // Runtime supplier selection
            try {
                $selection = $this->supplierSelector->selectBestSupplier(
                    $orderLine,
                    $order,
                    $this->getSelectionCriteria($order, $variant)
                );

                // Create supplier order with selected supplier
                SupplierOrder::create([
                    'order_id' => $order->id,
                    'order_line_id' => $orderLine->id,
                    'supplier_id' => $selection->primary['supplier']->id,
                    'supplier_product_id' => $selection->primary['supplier_product']->id,

                    // Status
                    'status' => SupplierOrder::STATUS_PENDING,

                    // Cost estimation
                    'estimated_cost_price' => $selection->primary['quote']['cost_price'],

                    // Revenue tracking
                    'order_line_unit_price' => $orderLine->unit_price->value,
                    'order_line_total' => $orderLine->total->value,

                    // Approval
                    'requires_approval' => $this->requiresApproval($variant, $selection),

                    // Artwork
                    'artwork_status' => $this->hasArtwork($cartLine) ? 'uploaded' : 'not_required',

                    // Delivery
                    'estimated_delivery_date' => $this->calculateDeliveryDate(
                        $selection->primary['quote']['lead_time'] ?? 96
                    ),

                    // Cancellation
                    'cancellation_deadline' => $this->calculateCancellationDeadline(
                        $selection->primary['supplier']
                    ),

                    // Selection metadata
                    'external_data' => [
                        'selection_score' => $selection->primary['score'],
                        'selection_reason' => $selection->getReason(),
                        'alternatives' => $selection->fallbacks->take(2)->toArray(),
                        'quote' => $selection->primary['quote'],
                        'selected_at' => now()->toIso8601String(),
                    ],
                ]);

            } catch (\Exception $e) {
                \Log::error('Failed to select supplier for order line', [
                    'order_line_id' => $orderLine->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        return $next($order);
    }

    protected function getSelectionCriteria(Order $order, $variant): array
    {
        return [
            'weights' => [
                'price' => 40,
                'lead_time' => 25,
                'priority' => 20,
                'shipping_cost' => 15,
            ],
        ];
    }

    protected function requiresApproval($variant, $selection): bool
    {
        // For now, all orders require approval
        // TODO: Add rules engine
        return true;
    }

    protected function hasArtwork($cartLine): bool
    {
        return $cartLine->printAssets()->exists();
    }

    protected function calculateDeliveryDate(?int $leadTimeHours): ?\Illuminate\Support\Carbon
    {
        if (!$leadTimeHours) {
            return null;
        }

        return now()->addHours($leadTimeHours);
    }

    protected function calculateCancellationDeadline($supplier): ?\Illuminate\Support\Carbon
    {
        $windowHours = $supplier->getMeta('cancellation_window', 24);

        return now()->addHours($windowHours);
    }
}
```

**Step 2: Commit**

```bash
git add packages/core/src/Pipelines/Order/Creation/CreateOrderLines.php
git commit -m "feat(core): integrate runtime supplier selection into order creation"
```

---

## Phase 4: Order Cancellation & Refunds

### Task 4.1: Order Cancellation Service

**Files:**
- Create: `packages/core/src/Services/OrderCancellationService.php`
- Create: `packages/core/src/DataTransferObjects/CancellationResult.php`
- Create: `packages/core/src/DataTransferObjects/SupplierOrderCancellationResult.php`
- Create: `packages/core/src/Exceptions/Orders/CannotCancelException.php`

**Step 1: Create result DTOs**

```php
<?php

namespace Lunar\DataTransferObjects;

use Illuminate\Support\Collection;

class CancellationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly Collection $cancelled,
        public readonly Collection $failed,
        public readonly int $totalRefund
    ) {}

    public function getErrorMessage(): string
    {
        return $this->failed->map(fn($f) => $f['reason'])->implode(', ');
    }
}

class SupplierOrderCancellationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly $supplierOrder,
        public readonly bool $supplierCancelled = false,
        public readonly int $cancellationFee = 0,
        public readonly $refund = null,
        public readonly ?string $error = null
    ) {}

    public static function success($supplierOrder, bool $supplierCancelled, int $cancellationFee, $refund): self
    {
        return new self(
            success: true,
            supplierOrder: $supplierOrder,
            supplierCancelled: $supplierCancelled,
            cancellationFee: $cancellationFee,
            refund: $refund
        );
    }

    public static function failed(string $error, $supplierOrder): self
    {
        return new self(
            success: false,
            supplierOrder: $supplierOrder,
            error: $error
        );
    }
}
```

**Step 2: Create CannotCancelException**

```php
<?php

namespace Lunar\Exceptions\Orders;

use Exception;

class CannotCancelException extends Exception
{
    //
}
```

**Step 3: Create OrderCancellationService**

```php
<?php

namespace Lunar\Services;

use Lunar\DataTransferObjects\CancellationResult;
use Lunar\DataTransferObjects\SupplierOrderCancellationResult;
use Lunar\Exceptions\Orders\CannotCancelException;
use Lunar\Events\SupplierOrderCancelled;
use Lunar\Managers\SupplierManager;
use Lunar\Models\Contracts\Order;
use Lunar\Models\Contracts\SupplierOrder;
use Lunar\Models\Contracts\User;

class OrderCancellationService
{
    public function __construct(
        protected SupplierManager $suppliers,
        protected RefundService $refunds
    ) {}

    /**
     * Cancel an entire order (all supplier orders)
     */
    public function cancelOrder(
        Order $order,
        User $user,
        string $reason,
        bool $customerInitiated = false
    ): CancellationResult {

        $results = [
            'cancelled' => [],
            'failed' => [],
            'refunds' => [],
        ];

        foreach ($order->supplierOrders as $supplierOrder) {
            try {
                $result = $this->cancelSupplierOrder(
                    $supplierOrder,
                    $user,
                    $reason,
                    $customerInitiated
                );

                if ($result->success) {
                    $results['cancelled'][] = $supplierOrder;
                    if ($result->refund) {
                        $results['refunds'][] = $result->refund;
                    }
                } else {
                    $results['failed'][] = [
                        'supplier_order' => $supplierOrder,
                        'reason' => $result->error,
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'supplier_order' => $supplierOrder,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        // Update main order status
        if (count($results['cancelled']) === $order->supplierOrders->count()) {
            $order->update(['status' => 'cancelled']);
        } elseif (count($results['cancelled']) > 0) {
            $order->update(['status' => 'partially_cancelled']);
        }

        return new CancellationResult(
            success: count($results['failed']) === 0,
            cancelled: collect($results['cancelled']),
            failed: collect($results['failed']),
            totalRefund: collect($results['refunds'])->sum('amount'),
        );
    }

    /**
     * Cancel a single supplier order
     */
    public function cancelSupplierOrder(
        SupplierOrder $supplierOrder,
        User $user,
        string $reason,
        bool $customerInitiated = false
    ): SupplierOrderCancellationResult {

        // Check if cancellable
        if (!$supplierOrder->canBeCancelled()) {
            return SupplierOrderCancellationResult::failed(
                'Order cannot be cancelled at this stage',
                $supplierOrder
            );
        }

        // Calculate cancellation fee
        $cancellationFee = $supplierOrder->getCancellationFee();

        // Try to cancel with supplier API
        $supplierCancelled = false;

        if ($supplierOrder->external_order_id) {
            try {
                $driver = $this->suppliers->supplier($supplierOrder->supplier);
                $supplierCancelled = $driver->cancelOrder($supplierOrder->external_order_id);
            } catch (\Exception $e) {
                \Log::error('Failed to cancel with supplier', [
                    'supplier_order_id' => $supplierOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update supplier order
        $supplierOrder->update([
            'status' => SupplierOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
            'cancellation_fee' => $cancellationFee,
            'cancellation_requested_at' => now(),
        ]);

        // Calculate refund
        $orderLineTotal = $supplierOrder->order_line_total;
        $refundAmount = $orderLineTotal - $cancellationFee;

        $refund = null;
        if ($refundAmount > 0 && $customerInitiated) {
            $refund = $this->refunds->processRefund(
                $supplierOrder->order,
                $refundAmount,
                "Cancelled: {$reason}"
            );

            $supplierOrder->update([
                'refund_amount' => $refundAmount,
                'refund_issued_at' => now(),
                'refund_reason' => $reason,
            ]);
        }

        // Log activity
        activity('supplier_orders')
            ->performedOn($supplierOrder)
            ->causedBy($user)
            ->withProperties([
                'cancelled_by_customer' => $customerInitiated,
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount,
                'supplier_cancelled' => $supplierCancelled,
            ])
            ->log('Supplier order cancelled');

        // Notify customer
        if ($customerInitiated) {
            event(new SupplierOrderCancelled($supplierOrder, $refund));
        }

        return SupplierOrderCancellationResult::success(
            $supplierOrder,
            $supplierCancelled,
            $cancellationFee,
            $refund
        );
    }
}
```

**Step 4: Update SupplierOrder model with cancellation methods**

```php
// Add to packages/core/src/Models/SupplierOrder.php

public function canBeCancelled(): bool
{
    // Already cancelled or delivered
    if (in_array($this->status, [
        self::STATUS_CANCELLED,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
    ])) {
        return false;
    }

    // Check supplier's allowed statuses
    $allowedStatuses = $this->supplier->getMeta('cancellation_allowed_statuses', [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_SUBMITTED,
    ]);

    if (!in_array($this->status, $allowedStatuses)) {
        return false;
    }

    // Check cancellation deadline
    if ($this->cancellation_deadline && now()->isAfter($this->cancellation_deadline)) {
        return false;
    }

    return true;
}

public function getCancellationFee(): int
{
    return match($this->status) {
        self::STATUS_PENDING,
        self::STATUS_ARTWORK_READY => 0,

        self::STATUS_APPROVED,
        self::STATUS_SUBMITTED => (int) ($this->estimated_cost_price * 0.1),

        self::STATUS_PROCESSING => (int) ($this->estimated_cost_price * 0.5),

        default => $this->estimated_cost_price,
    };
}

public function getCancellationTimeRemaining(): ?string
{
    if (!$this->canBeCancelled() || !$this->cancellation_deadline) {
        return null;
    }

    return $this->cancellation_deadline->diffForHumans();
}
```

**Step 5: Commit**

```bash
git add packages/core/src/Services/OrderCancellationService.php
git add packages/core/src/DataTransferObjects/
git add packages/core/src/Exceptions/Orders/CannotCancelException.php
git add packages/core/src/Models/SupplierOrder.php
git commit -m "feat(core): add order cancellation service with refund support"
```

---

## Phase 5: Payment & Refund Integration

### Task 5.1: Refund Service

**Files:**
- Create: `packages/core/src/Services/RefundService.php`
- Create: `packages/core/src/Models/Refund.php`
- Create: `packages/core/database/migrations/2026_01_12_200000_create_refunds_table.php`

**Step 1: Create refunds migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lunar_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('lunar_orders');
            $table->foreignId('supplier_order_id')->nullable()->constrained('lunar_supplier_orders');
            $table->string('payment_intent_id')->nullable();
            $table->string('refund_id')->nullable()->comment('External refund ID from payment provider');
            $table->integer('amount')->comment('in cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('method')->default('original')->comment('original, store_credit, manual');
            $table->string('status')->default('pending')->comment('pending, processing, completed, failed');
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('refund_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunar_refunds');
    }
};
```

**Step 2: Create Refund model**

```php
<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Base\BaseModel;
use Lunar\Base\Casts\Price;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;

class Refund extends BaseModel
{
    use HasMacros, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'amount' => Price::class,
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::modelClass());
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::modelClass());
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }
}
```

**Step 3: Create RefundService**

```php
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
```

**Step 4: Run migration**

```bash
php artisan migrate --path=packages/core/database/migrations/2026_01_12_200000_create_refunds_table.php
```

**Step 5: Commit**

```bash
git add packages/core/src/Services/RefundService.php
git add packages/core/src/Models/Refund.php
git add packages/core/database/migrations/2026_01_12_200000_create_refunds_table.php
git commit -m "feat(core): add refund service with Stripe integration"
```

---

## Phase 6: Email Notifications & Communication

**Due to length constraints, I'll create a summary for remaining phases:**

### Phase 6 Summary: Email Notifications
- Task 6.1: Create notification events (SupplierOrderSubmitted, SupplierOrderShipped, etc.)
- Task 6.2: Create email templates (order-submitted.blade.php, order-shipped.blade.php, etc.)
- Task 6.3: Create notification preferences system
- Task 6.4: Queue notification jobs

### Phase 7 Summary: Quality Issues & Reprints
- Task 7.1: Create supplier_issues table migration
- Task 7.2: Create SupplierIssue model and service
- Task 7.3: Admin interface for reporting issues
- Task 7.4: Reprint workflow

### Phase 8 Summary: Customer Portal & Tracking
- Task 8.1: OrderActivityService for timeline generation
- Task 8.2: Livewire order tracking component
- Task 8.3: Cancellation request interface
- Task 8.4: Order timeline with supplier updates

### Phase 9 Summary: Admin Dashboard
- Task 9.1: Supplier order approval queue (Filament resource)
- Task 9.2: Bulk operations interface
- Task 9.3: Profit margin reports
- Task 9.4: Supplier performance tracking

---

## Execution Options

**Plan complete and saved to `docs/plans/2026-01-12-multi-supplier-fulfillment-system.md`.**

This is a comprehensive plan covering:
- ✅ Core infrastructure with Internal supplier
- ✅ HelloPrint and print.com drivers
- ✅ Runtime supplier selection
- ✅ Order cancellation with refunds
- ✅ Payment integration
- 📝 Email notifications (summary)
- 📝 Quality issues (summary)
- 📝 Customer portal (summary)
- 📝 Admin dashboard (summary)

**Two execution options:**

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

**Which approach do you prefer?**
