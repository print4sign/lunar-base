# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a monorepo for **LunarPHP**, a Laravel e-commerce platform. The `sandbox/` directory contains a demo storefront application that uses the core Lunar packages via path repositories.

## Project Vision

Lunar already provides a strong commerce core (products/variants, attributes/options, pricing, cart, orders, admin). The current evolution focuses on making **fulfillment** and **supplier-driven products** first-class concepts.

This is especially important for complex industries like **print-on-demand** where suppliers provide:
- Large product catalogs
- Interactive configurators
- Dynamic pricing based on dimensions/quantities/options
- Order placement APIs
- File upload requirements and specifications

The goal is to abstract these supplier integrations behind a driver pattern, allowing Lunar to seamlessly work with multiple fulfillment providers while maintaining a consistent API for the storefront.

## Repository Structure

```
lunar-base/
├── packages/           # Core Lunar packages (symlinked to sandbox/vendor)
│   ├── core/          # E-commerce core: models, actions, pipelines, facades
│   ├── admin/         # Filament-based admin panel
│   ├── scraper/       # Product scraper from external sources
│   ├── stripe/        # Stripe payment integration
│   ├── paypal/        # PayPal payment integration
│   ├── opayo/         # Opayo payment integration
│   ├── search/        # Scout search base
│   ├── meilisearch/   # Meilisearch integration
│   └── table-rate-shipping/
├── sandbox/           # Demo Laravel storefront app (current directory)
├── tests/             # Monorepo-level tests organized by package
└── docs/              # Documentation
```

## Development Commands

### Sandbox Application (from sandbox/)

```bash
# Start all services (server, queue, pail, vite)
composer dev

# Run tests
composer test

# Build frontend assets
npm run build
npm run dev
```

### Monorepo Level (from lunar-base/)

```bash
# Run all tests with Pest
composer test:pest

# Run specific test suite
./vendor/bin/pest --testsuite=core
./vendor/bin/pest --testsuite=admin

# Run single test file
./vendor/bin/pest tests/core/Unit/SomeTest.php

# Static analysis
composer test:phpstan

# Code formatting
composer pint
```

## Architecture

### Core Package (`packages/core/`)

**Models** (`src/Models/`): Product, ProductVariant, Cart, CartLine, Order, OrderLine, Customer, Collection, Brand, Price, Supplier, SupplierProduct, etc.

**Model Contracts** (`src/Models/Contracts/`): Interfaces for all models allowing custom implementations via ModelManifest.

**Facades** (`src/Facades/`):
- `ModelManifest` - Register/replace model bindings
- `CartSession` - Cart session management
- `Pricing` - Pricing calculations
- `Taxes` - Tax calculations
- `ShippingManifest` - Shipping options
- `Suppliers` - Supplier driver management
- `Discounts` - Discount management
- `Payments` - Payment processing

**Pipelines** (`src/Pipelines/`): Cart and order calculation pipelines configured in `config/lunar/cart.php`. Key pipelines:
- `Cart/` - CalculateLines, ApplyShipping, ApplyDiscounts, CalculateTax
- `CartLine/` - GetUnitPrice, ApplyDimensionPricing, GetSupplierPrice
- `Order/Creation/` - CreateOrderLines, etc.

**Actions** (`src/Actions/`): Business logic for carts, orders, collections, currencies, taxes.

**Supplier Drivers** (`src/Drivers/Suppliers/`): AbstractSupplierDriver, ProboDriver, HelloPrintDriver, OfflineDriver.

### Admin Package (`packages/admin/`)

Built on **Filament v3**. Key components in `src/Filament/`:
- `Resources/` - CRUD for Products, Orders, Customers, Collections, Discounts, etc.
- `Pages/` - Custom admin pages
- `Widgets/` - Dashboard widgets

### Sandbox Storefront (`sandbox/`)

**Livewire Pages** (`app/Livewire/Pages/`): Home, ProductPage, ProductsIndex, CollectionPage, CheckoutPage, Dashboard pages.

**Livewire Components** (`app/Livewire/Components/`): AddToCart, CartSidebar, ProboConfigurator, etc.

**Multi-locale routing**: Routes support nl/en/de/es with localized URL segments (see `routes/web.php`).

## Configuration

Lunar config files are in `config/lunar/`:
- `cart.php` - Cart pipelines, actions, validators
- `orders.php` - Order configuration
- `pricing.php` - Pricing rules
- `suppliers.php` - Supplier driver config
- `urls.php` - URL generation

## Testing

Tests use **Pest** with Orchestra Testbench. Test files are in `tests/` at monorepo level, organized by package (admin/, core/, stripe/, etc.).

Key test utilities in `tests/Pest.php`:
- `buildCart()` - Helper to create cart with currency, tax class, and line items
- `setAuthUserConfig()` - Configure auth user for testing

The `TestCase.php` supports model replacement testing via `LUNAR_TESTING_REPLACE_MODELS` env var.

## Key Patterns

1. **Model Replacement**: Custom model implementations via `ModelManifest::replace()` and contract interfaces
2. **Pipeline Architecture**: Cart/order calculations run through configurable pipeline classes
3. **Supplier Drivers**: External product/pricing fetched through driver pattern (Probo, HelloPrint)
4. **Filament Resources**: Admin CRUD follows Filament v3 resource patterns with Pages and RelationManagers
