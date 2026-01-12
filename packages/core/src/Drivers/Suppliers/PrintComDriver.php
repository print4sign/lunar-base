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

            // Check if there are more items - if we got fewer than requested, we're done
            if (count($products) < $limit) {
                break;
            }

            $offset += $limit;
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
        return new ConfiguratorResponse(
            productCode: $externalId,
            availableOptions: [],
            selectedOptions: $selections,
            canOrder: isset($selections['sku']),
        );
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

        // Add validation
        if (!$variant->supplierProduct) {
            return OrderResponse::failed(
                'Product variant has no supplier product configured',
                ['variant_id' => $variant->id]
            );
        }

        $shippingAddress = $order->shippingAddress;

        $orderData = [
            'mode' => $this->getConfig('sandbox', false) ? 'test' : 'live',
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

        return $printAsset->getTemporaryUrl(3600);
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
            $uploaders[] = [
                'type' => 'single',
                'amount' => 1,
                'width' => $artwork['dimensions']['width'] ?? null,
                'height' => $artwork['dimensions']['height'] ?? null,
                'minimal_dpi' => $artwork['minDpi'] ?? 300,
                'file_limit' => $artwork['maxSizeMb'] ?? 50,
            ];
        }

        return UploadSpec::fromArray([
            'upload' => true,
            'uploaders' => $uploaders,
            'version' => UploadSpec::VERSION,
            'source' => UploadSpec::SOURCE_SUPPLIER . ':printcom',
        ]);
    }

    public function requiresUpload(string $externalId, array $configuration): bool
    {
        $spec = $this->resolveUploadSpec($externalId, $configuration);

        return $spec?->requiresUpload() ?? false;
    }
}
