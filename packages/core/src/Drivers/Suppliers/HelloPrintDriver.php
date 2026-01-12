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

        return new ConfiguratorResponse(
            productCode: $externalId,
            availableOptions: [],
            selectedOptions: $selections,
            canOrder: isset($selections['variantKey']),
        );
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
            $uploaders[] = [
                'type' => 'frontback',
                'amount' => 2,
                'width' => $specs['dimensions']['width'] ?? null,
                'height' => $specs['dimensions']['height'] ?? null,
                'unit' => $specs['dimensions']['unit'] ?? 'mm',
                'accepted_formats' => $specs['acceptedFileTypes'] ?? ['pdf', 'jpg', 'png'],
                'max_file_size' => ($specs['maxFileSizeMb'] ?? 50) * 1024 * 1024,
            ];
        } else {
            $uploaders[] = [
                'type' => 'single',
                'amount' => 1,
                'width' => $specs['dimensions']['width'] ?? null,
                'height' => $specs['dimensions']['height'] ?? null,
                'unit' => $specs['dimensions']['unit'] ?? 'mm',
                'accepted_formats' => $specs['acceptedFileTypes'] ?? ['pdf', 'jpg', 'png'],
                'max_file_size' => ($specs['maxFileSizeMb'] ?? 50) * 1024 * 1024,
            ];
        }

        return UploadSpec::fromArray([
            'upload' => true,
            'uploaders' => $uploaders,
            'version' => UploadSpec::VERSION,
            'source' => UploadSpec::SOURCE_SUPPLIER . ':helloprint',
        ]);
    }

    public function requiresUpload(string $externalId, array $configuration): bool
    {
        // Quick check without full spec resolution
        // For HelloPrint, most products require uploads
        return true;
    }
}
