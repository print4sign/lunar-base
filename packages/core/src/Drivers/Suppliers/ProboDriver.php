<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\ShippingOptionResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Exceptions\Suppliers\SupplierException;
use Lunar\Models\Contracts\SupplierOrder;
use Lunar\Models\SupplierProduct;

class ProboDriver extends AbstractSupplierDriver
{
    /**
     * The base URL for the Probo API.
     */
    protected string $baseUrl = 'https://api.proboprints.com/';

    /**
     * Get the capabilities of this driver.
     */
    public function capabilities(): array
    {
        return [
            'catalog_sync',
            'pricing',
            'ordering',
            'file_upload',
            'tracking',
            'configurator',
        ];
    }

    /**
     * Get cart line pipelines for this driver.
     */
    public function getCartLinePipelines(): array
    {
        return [
            \Lunar\Drivers\Suppliers\Probo\Pipelines\GetProboPrice::class,
        ];
    }

    /**
     * Create an HTTP client instance with Probo authentication.
     */
    protected function http(): PendingRequest
    {
        $apiKey = $this->getCredential('api_key');

        return parent::http()
            ->withHeaders([
                'Authorization' => "Basic {$apiKey}",
            ]);
    }

    /**
     * Sync the product catalog from Probo.
     */
    public function syncCatalog(): Collection
    {
        $allProducts = collect();
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $response = $this->http()->get('products', [
                'page' => $page,
                'per_page' => 100,
            ]);

            if (! $response->successful()) {
                throw new SupplierException('Failed to fetch Probo catalog: '.$response->body());
            }

            $data = $response->json();
            $products = $data['data'] ?? [];
            $meta = $data['meta'] ?? [];

            foreach ($products as $product) {
                // Skip inactive products
                if (! ($product['active'] ?? true)) {
                    continue;
                }

                $supplierProduct = SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => $this->supplier->id,
                        'external_id' => $product['code'],
                    ],
                    [
                        'external_name' => $this->extractProductName($product),
                        'external_data' => $product,
                        'configurator_schema' => $this->extractConfiguratorSchema($product),
                        'synced' => true,
                        'last_synced_at' => now(),
                    ]
                );

                $allProducts->push($supplierProduct);
            }

            $page++;
            $hasMorePages = $page <= ($meta['pages'] ?? 1);
        }

        return $allProducts;
    }

    /**
     * Extract the product name from Probo product data.
     */
    protected function extractProductName(array $product): string
    {
        // Try English translation first, then Dutch, then code
        return $product['translations']['en']['title']
            ?? $product['translations']['nl']['title']
            ?? $product['name']
            ?? $product['code'];
    }

    /**
     * Get a single product from Probo.
     */
    public function getProduct(string $externalId): ?array
    {
        $response = $this->http()->get("products/{$externalId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get the configurator schema for a product.
     */
    public function getConfiguratorSchema(string $externalId): ?array
    {
        $response = $this->http()->get("products/{$externalId}/configure");

        if (! $response->successful()) {
            return null;
        }

        return $this->extractConfiguratorSchema($response->json());
    }

    /**
     * Configure a product iteratively using Probo's configure endpoint.
     *
     * This method calls Probo's POST /products/configure endpoint
     * which returns available options based on current selections.
     *
     * @see https://apidocs.proboprints.com/examples/configure-examples
     */
    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        // Build options array from selections
        $options = [];
        foreach ($selections as $code => $value) {
            if (in_array($code, ['hash', 'quantity'])) {
                continue;
            }
            $options[] = [
                'code' => (string) $code,
                'value' => (string) $value,
            ];
        }

        $requestData = [
            'language' => app()->getLocale() === 'nl' ? 'nl' : 'en',
            'products' => [
                [
                    'code' => $externalId,
                    'options' => $options,
                ],
            ],
        ];

        $response = $this->http()->post('products/configure', $requestData);

        if (! $response->successful()) {
            throw new SupplierException(
                "Failed to configure product {$externalId} from Probo: ".$response->body()
            );
        }

        $data = $response->json();

        // The response contains a products array with our configured product
        $productData = $data['products'][0] ?? $data;

        // Probo may return status: error but still provide available_options
        // This happens when the configuration path is incomplete/invalid
        // We only throw an error if there are no available_options AND no can_order
        $availableOptions = $productData['available_options'] ?? [];
        $canOrder = $productData['can_order'] ?? false;
        $hasError = ($data['status'] ?? '') === 'error' || ($productData['status'] ?? '') === 'error';
        $errorMessage = $data['message'] ?? $productData['message'] ?? null;

        // Only throw if it's a real error (no options and no can_order)
        if ($hasError && empty($availableOptions) && ! $canOrder) {
            throw new SupplierException(
                "Failed to configure product {$externalId} from Probo: ".($errorMessage ?? 'Unknown error')
            );
        }

        return ConfiguratorResponse::fromProboResponse($externalId, [
            'available_options' => $availableOptions,
            'selected_options' => $productData['selected_options'] ?? $selections,
            'can_order' => $canOrder,
            // Don't treat "Can not build valid path" as an error - it's expected during configuration
            'errors' => ($hasError && empty($availableOptions)) ? $errorMessage : null,
            'product' => $productData,
        ]);
    }

    /**
     * Determine if the configuration is complete enough to order.
     */
    protected function canOrderWithSelections(array $data, array $selections): bool
    {
        $availableOptions = $data['available_options'] ?? $data['options'] ?? [];

        foreach ($availableOptions as $option) {
            $code = $option['code'] ?? $option['handle'] ?? null;
            $required = $option['required'] ?? true;

            if ($required && $code && ! isset($selections[$code])) {
                return false;
            }
        }

        return ! empty($selections);
    }

    /**
     * Get price with shipping options for a configured product.
     *
     * This extends getPrice() to include shipping options from Probo.
     * The price API uses 'code' and options array (amount is part of options).
     */
    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        // Build options including amount
        $options = $this->mapConfigurationToProboOptions($configuration);

        // Add amount/quantity to options if not already present
        $hasAmount = collect($options)->contains(fn ($o) => $o['code'] === 'amount');
        if (! $hasAmount) {
            $options[] = [
                'code' => 'amount',
                'value' => (string) ($configuration['quantity'] ?? $configuration['amount'] ?? 1),
            ];
        }

        $productData = [
            'code' => $externalId,
            'options' => $options,
        ];

        $requestData = [
            'products' => [$productData],
        ];

        // Add delivery address if provided for shipping calculation
        if ($address) {
            $requestData['deliveries'] = [
                [
                    'address' => [
                        'country' => $address['country'] ?? $address['country_code'] ?? 'NL',
                        'postal_code' => $address['postcode'] ?? $address['postal_code'] ?? null,
                        'city' => $address['city'] ?? null,
                    ],
                ],
            ];
        }

        logger()->debug('ProboDriver: getPriceWithShipping request', [
            'externalId' => $externalId,
            'configuration' => $configuration,
            'requestData' => $requestData,
        ]);

        $response = $this->http()->post('price', $requestData);

        logger()->debug('ProboDriver: getPriceWithShipping response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new SupplierException('Failed to get price with shipping from Probo: '.$response->body());
        }

        $data = $response->json();

        // Extract shipping options if available
        $shippingOptions = $this->extractShippingOptions($data);

        // Probo now returns a 'prices' array with different delivery speed options
        // Each entry has different production_hours and rush_surcharge
        // We use the standard delivery option (96 hours / 4 days, no rush surcharge)
        // or fall back to the last option (cheapest) if 96h not available
        $prices = $data['prices'] ?? [];
        $priceData = null;

        if (! empty($prices)) {
            // Try to find the 96-hour (standard) option first
            foreach ($prices as $price) {
                if (($price['production_hours'] ?? '') === '96') {
                    $priceData = $price;
                    break;
                }
            }
            // If no 96h option, use the last one (longest production time, cheapest)
            if (! $priceData) {
                $priceData = end($prices);
            }
        }

        // Fall back to root-level prices for backwards compatibility
        if (! $priceData) {
            $priceData = $data;
        }

        // Probo returns prices in EUR (convert to cents)
        $costPrice = (int) (($priceData['products_purchase_price'] ?? 0) * 100);
        $sellPrice = (int) (($priceData['products_sales_price'] ?? 0) * 100);

        return new PriceResponse(
            costPrice: $costPrice,
            sellPrice: $sellPrice,
            currency: 'EUR',
            compareCostPrice: isset($priceData['products_purchase_base_price'])
                ? (int) ($priceData['products_purchase_base_price'] * 100)
                : null,
            breakdown: [
                'base_price' => $priceData['products_purchase_base_price'] ?? null,
                'products_price' => $priceData['products_purchase_price'] ?? null,
                'shipping_price' => $data['shipping_purchase_price'] ?? null,
                'rush_surcharge' => $priceData['products_purchase_rush_surcharge'] ?? null,
                'production_hours' => $priceData['production_hours'] ?? null,
                'vat_rate' => $data['vat_rate'] ?? null,
            ],
            meta: [
                'raw_response' => $data,
                'all_price_options' => $prices,
                'shipping_options' => array_map(fn ($opt) => $opt->toArray(), $shippingOptions),
                'selected_shipping' => $data['selected_shipping_method'] ?? null,
            ],
        );
    }

    /**
     * Extract shipping options from Probo price response.
     *
     * @return ShippingOptionResponse[]
     */
    protected function extractShippingOptions(array $data): array
    {
        $options = [];

        // Check for shipping_options in the response
        $shippingData = $data['shipping_options']
            ?? $data['delivery_options']
            ?? $data['shipping_methods']
            ?? [];

        foreach ($shippingData as $option) {
            $options[] = ShippingOptionResponse::fromProboOption($option);
        }

        // If no explicit shipping options, but we have shipping price info
        if (empty($options) && isset($data['shipping_purchase_price'])) {
            $options[] = new ShippingOptionResponse(
                identifier: 'probo_standard',
                name: 'Standard Shipping',
                priceInCents: (int) (($data['shipping_purchase_price'] ?? 0) * 100),
                currency: 'EUR',
                estimatedDelivery: $data['estimated_delivery'] ?? null,
            );
        }

        return $options;
    }

    /**
     * Get available delivery dates for a configuration.
     * The price API uses 'code' and options array (amount is part of options).
     */
    public function getDeliveryDates(string $externalId, array $configuration, ?array $address = null): array
    {
        // Build options including amount
        $options = $this->mapConfigurationToProboOptions($configuration);

        // Add amount/quantity to options if not already present
        $hasAmount = collect($options)->contains(fn ($o) => $o['code'] === 'amount');
        if (! $hasAmount) {
            $options[] = [
                'code' => 'amount',
                'value' => (string) ($configuration['quantity'] ?? $configuration['amount'] ?? 1),
            ];
        }

        $requestData = [
            'products' => [
                [
                    'code' => $externalId,
                    'options' => $options,
                ],
            ],
        ];

        if ($address) {
            $requestData['deliveries'] = [
                [
                    'address' => [
                        'country' => $address['country'] ?? 'NL',
                        'postal_code' => $address['postcode'] ?? null,
                    ],
                ],
            ];
        }

        $response = $this->http()->post('price', $requestData);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return [
            'dates' => $data['delivery_dates'] ?? [],
            'presets' => $data['delivery_date_presets'] ?? ['standard', 'express'],
            'estimated' => $data['estimated_delivery'] ?? null,
        ];
    }

    /**
     * Extract a normalized configurator schema from Probo product data.
     */
    protected function extractConfiguratorSchema(array $productData): array
    {
        $options = $productData['options'] ?? [];
        $schema = [
            'dimensions' => [],
            'options' => [],
            'quantity' => [
                'min' => 1,
                'max' => 1000,
            ],
        ];

        foreach ($options as $option) {
            $handle = $option['code'] ?? $option['name'] ?? '';

            // Check if it's a dimension option
            if (in_array(strtolower($handle), ['width', 'height', 'length'])) {
                $schema['dimensions'][$handle] = [
                    'type' => 'number',
                    'unit' => $option['unit'] ?? 'mm',
                    'min' => $option['min'] ?? 1,
                    'max' => $option['max'] ?? 10000,
                    'step' => $option['step'] ?? 1,
                    'required' => $option['required'] ?? true,
                ];
            } else {
                // Regular option
                $schema['options'][$handle] = [
                    'type' => isset($option['values']) ? 'select' : 'text',
                    'label' => $option['name'] ?? $handle,
                    'choices' => $option['values'] ?? [],
                    'default' => $option['default'] ?? null,
                    'required' => $option['required'] ?? false,
                ];
            }
        }

        return $schema;
    }

    /**
     * Get the price for a product with configuration.
     * The price API uses 'code' and options array (amount is part of options).
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        // Build options including amount
        $options = $this->mapConfigurationToProboOptions($configuration);

        // Add amount/quantity to options if not already present
        $hasAmount = collect($options)->contains(fn ($o) => $o['code'] === 'amount');
        if (! $hasAmount) {
            $options[] = [
                'code' => 'amount',
                'value' => (string) ($configuration['quantity'] ?? $configuration['amount'] ?? 1),
            ];
        }

        $response = $this->http()->post('price', [
            'products' => [
                [
                    'code' => $externalId,
                    'options' => $options,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new SupplierException('Failed to get price from Probo: '.$response->body());
        }

        $data = $response->json();

        // Probo returns a 'prices' array with different delivery speed options
        $prices = $data['prices'] ?? [];
        $priceData = null;

        if (! empty($prices)) {
            // Try to find the 96-hour (standard) option first
            foreach ($prices as $price) {
                if (($price['production_hours'] ?? '') === '96') {
                    $priceData = $price;
                    break;
                }
            }
            // If no 96h option, use the last one (longest production time, cheapest)
            if (! $priceData) {
                $priceData = end($prices);
            }
        }

        // Fall back to root-level prices for backwards compatibility
        if (! $priceData) {
            $priceData = $data;
        }

        // Probo returns prices in EUR (convert to cents)
        $costPrice = (int) (($priceData['products_purchase_price'] ?? 0) * 100);
        $sellPrice = (int) (($priceData['products_sales_price'] ?? 0) * 100);

        return new PriceResponse(
            costPrice: $costPrice,
            sellPrice: $sellPrice,
            currency: 'EUR',
            compareCostPrice: isset($priceData['products_purchase_base_price'])
                ? (int) ($priceData['products_purchase_base_price'] * 100)
                : null,
            breakdown: [
                'base_price' => $priceData['products_purchase_base_price'] ?? null,
                'rush_surcharge' => $priceData['products_purchase_rush_surcharge'] ?? null,
                'production_hours' => $priceData['production_hours'] ?? null,
                'vat_rate' => $data['vat_rate'] ?? null,
            ],
            meta: [
                'raw_response' => $data,
                'all_price_options' => $prices,
            ],
        );
    }

    /**
     * Get bulk prices for multiple products.
     * The price API uses 'code' and options array (amount is part of options).
     */
    public function getBulkPrices(array $items): Collection
    {
        $products = collect($items)->map(function ($item) {
            $options = $this->mapConfigurationToProboOptions($item['configuration'] ?? []);

            // Add amount/quantity to options if not already present
            $hasAmount = collect($options)->contains(fn ($o) => $o['code'] === 'amount');
            if (! $hasAmount) {
                $options[] = [
                    'code' => 'amount',
                    'value' => (string) ($item['quantity'] ?? 1),
                ];
            }

            return [
                'code' => $item['external_id'],
                'options' => $options,
            ];
        })->values()->all();

        $response = $this->http()->post('price', [
            'products' => $products,
        ]);

        if (! $response->successful()) {
            throw new SupplierException('Failed to get bulk prices from Probo: '.$response->body());
        }

        // Parse the bulk response
        return collect($items)->map(function ($item) use ($response) {
            $data = $response->json();

            return new PriceResponse(
                costPrice: (int) (($data['products_purchase_price'] ?? 0) * 100),
                sellPrice: (int) (($data['products_sales_price'] ?? 0) * 100),
                currency: 'EUR',
            );
        });
    }

    /**
     * Map our configuration format to Probo's options format for configure endpoint.
     * Returns array of objects: [{"code": "width", "value": "100"}, ...]
     */
    protected function mapConfigurationToProboOptions(array $configuration): array
    {
        $options = [];

        foreach ($configuration as $key => $value) {
            // Skip internal keys
            if (in_array($key, ['hash', 'quantity'])) {
                continue;
            }

            // Skip cross-sell skip flags (e.g., 'accessories-cross-sell_skip')
            if (str_ends_with($key, '_skip')) {
                continue;
            }

            $options[] = [
                'code' => (string) $key,
                'value' => (string) $value,
            ];
        }

        return $options;
    }

    /**
     * Submit an order to Probo.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        $orderLine = $supplierOrder->orderLine;
        $variant = $orderLine->purchasable;
        $order = $supplierOrder->order;

        // Get shipping address
        $shippingAddress = $order->shippingAddress;

        $orderData = [
            'order_type' => $this->getConfig('sandbox', false) ? 'test' : 'live',
            'reference' => $order->reference.'-'.$orderLine->id,
            'contact_email' => $order->billingAddress?->contact_email ?? $shippingAddress?->contact_email,
            'deliveries' => [
                [
                    'delivery_date_preset' => 'standard',
                    'shipping_method_preset' => 'cheapest',
                    'address' => [
                        'name' => $shippingAddress?->fullName ?? $shippingAddress?->company_name,
                        'street' => $shippingAddress?->line_one,
                        'street2' => $shippingAddress?->line_two,
                        'postal_code' => $shippingAddress?->postcode,
                        'city' => $shippingAddress?->city,
                        'country' => $shippingAddress?->country?->iso2,
                        'phone' => $shippingAddress?->contact_phone,
                        'email' => $shippingAddress?->contact_email,
                    ],
                ],
            ],
            'products' => [
                [
                    'code' => $variant->supplierProduct?->external_id,
                    'options' => $this->mapConfigurationToProboOptions($variant->configuration ?? []),
                    'amount' => $orderLine->quantity,
                    'reference' => (string) $orderLine->id,
                    // Files would need to be added here for print products
                ],
            ],
        ];

        $response = $this->http()->post('order', $orderData);

        if (! $response->successful()) {
            return OrderResponse::failed(
                'Failed to submit order to Probo: '.$response->body(),
                ['response' => $response->json()]
            );
        }

        $data = $response->json();

        return OrderResponse::success(
            externalId: $data['id'] ?? $data['order_id'] ?? uniqid('probo-'),
            status: $data['status'] ?? 'submitted',
            data: $data
        );
    }

    /**
     * Get the status of an order.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        $response = $this->http()->get("order/{$externalOrderId}");

        if (! $response->successful()) {
            throw new SupplierException('Failed to get order status from Probo: '.$response->body());
        }

        $data = $response->json();

        return new StatusResponse(
            status: $this->mapProboStatus($data['status'] ?? 'unknown'),
            externalId: $externalOrderId,
            tracking: $this->extractTracking($data),
            estimatedDelivery: $data['estimated_delivery'] ?? null,
            data: $data
        );
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(string $externalOrderId): bool
    {
        $response = $this->http()->delete("order/{$externalOrderId}");

        return $response->successful();
    }

    /**
     * Map Probo status to our internal status.
     */
    protected function mapProboStatus(string $status): string
    {
        return match (strtolower($status)) {
            'draft', 'pending' => 'pending',
            'accepted', 'production', 'in_production' => 'processing',
            'shipped', 'dispatched' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'error', 'failed' => 'failed',
            default => $status,
        };
    }

    /**
     * Extract tracking information from order data.
     */
    protected function extractTracking(array $data): ?array
    {
        if (empty($data['shipments'])) {
            return null;
        }

        $tracking = [
            'numbers' => [],
            'url' => null,
        ];

        foreach ($data['shipments'] as $shipment) {
            if (isset($shipment['tracking_code'])) {
                $tracking['numbers'][] = $shipment['tracking_code'];
            }
            if (isset($shipment['tracking_url']) && empty($tracking['url'])) {
                $tracking['url'] = $shipment['tracking_url'];
            }
        }

        return $tracking;
    }
}
