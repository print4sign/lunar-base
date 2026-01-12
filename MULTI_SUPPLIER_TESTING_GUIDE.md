# Multi-Supplier Fulfillment System - Testing & Usage Guide

## 📋 Overzicht

Dit systeem implementeert volledige multi-supplier fulfillment voor Lunar Core met automatische supplier selectie, order routing, cancellation en refunds.

## 🎯 Waar vind je alles?

### Admin Panel (Filament)

**Locatie**: `http://your-site.test/admin`

#### 1. Supplier Orders Management
- **Menu**: Fulfillment → Supplier Orders
- **Badge**: Toont aantal orders die goedkeuring nodig hebben
- **Acties**:
  - ✅ Approve artwork
  - ❌ Cancel order (met reden)
  - 👁️ View volledige details
  - ✏️ Edit order data

#### 2. Suppliers (Bestaand)
- **Menu**: Catalog → Suppliers
- **Locatie**: `packages/admin/src/Filament/Resources/SupplierResource.php`
- **Configuratie**:
  - Driver selectie (probo, helloprint, printcom, internal, offline)
  - API credentials
  - Capabilities
  - Metadata (cancellation windows, etc.)

#### 3. Dashboard Widgets
- **Supplier Performance Widget**: `packages/admin/src/Filament/Widgets/SupplierPerformanceWidget.php`
  - Toont performance metrics per supplier
  - Delivery rates, profit margins, issues

### Backend Services

Alle services zitten in `packages/core/src/Services/`:

1. **RuntimeSupplierSelector** - Automatische supplier keuze
2. **OrderCancellationService** - Order cancellations
3. **RefundService** - Refund processing
4. **SupplierIssueService** - Quality issue tracking
5. **OrderActivityService** - Customer timeline
6. **SupplierMetricsService** - Analytics

### Database

Alle tabellen met prefix `lunar_`:
- `lunar_supplier_orders` - Supplier order tracking
- `lunar_refunds` - Refund records
- `lunar_supplier_issues` - Quality issues
- `lunar_suppliers` - Supplier configuratie (bestaand)
- `lunar_supplier_products` - Product mappings (bestaand)

## 🧪 Stap-voor-stap Testing

### Stap 1: Suppliers Configureren

```bash
# Ga naar admin panel
http://your-site.test/admin/suppliers
```

**Maak een supplier aan:**

1. Klik "New Supplier"
2. Vul in:
   - **Name**: "HelloPrint Test"
   - **Driver**: helloprint
   - **Enabled**: Yes
   - **Priority**: 50

3. Klik "Credentials" tab:
   ```json
   {
     "api_key": "your-helloprint-api-key"
   }
   ```

4. Klik "Capabilities" tab:
   ```json
   {
     "catalog_sync": true,
     "pricing": true,
     "ordering": true,
     "tracking": true,
     "cancellation_window_hours": 24
   }
   ```

5. Save

**Herhaal voor andere suppliers:**
- print.com (driver: printcom)
- Internal (driver: internal)
- Probo (driver: probo)

### Stap 2: Test Product met Supplier

```bash
# Ga naar Products
http://your-site.test/admin/products
```

1. Open een bestaand product
2. Ga naar "Variants" tab
3. Klik op een variant
4. Scroll naar "Fulfillment" sectie
5. Configureer:
   - **Supplier Product**: Selecteer een supplier product
   - **Is Dynamic**: Yes (voor real-time pricing)
   - **Margin**: 30% (bijvoorbeeld)

### Stap 3: Plaats een Test Order

**Via Storefront:**

```bash
http://your-site.test/products/your-product
```

1. Voeg product toe aan cart
2. Ga naar checkout
3. Plaats order

**Wat gebeurt er automatisch:**

1. ✅ **CreateOrderLines pipeline** roept RuntimeSupplierSelector aan
2. ✅ **Supplier wordt geselecteerd** op basis van:
   - 40% prijs
   - 25% levertijd
   - 20% prioriteit
   - 15% verzendkosten

3. ✅ **SupplierOrder wordt aangemaakt** met:
   - Geschatte kostprijs
   - Revenue tracking
   - Estimated delivery date
   - Cancellation deadline
   - Selection metadata (score, alternatives)

### Stap 4: Bekijk Supplier Order in Admin

```bash
http://your-site.test/admin/supplier-orders
```

Je ziet:
- Order referentie
- Supplier naam
- Status badge
- Kosten en revenue
- Profit margin
- Timelines

**Klik op "View" om te zien:**
- Volledige order details
- Financial breakdown
- Selection data (waarom deze supplier gekozen is)
- Alternatives (welke andere suppliers beschikbaar waren)

### Stap 5: Test Approval Workflow

**Als order artwork approval nodig heeft:**

1. Ga naar Supplier Orders
2. Badge toont aantal "requires approval"
3. Filter op "Requires Approval"
4. Klik op order
5. Klik "Approve" actie
6. Status update naar "approved"

### Stap 6: Test Cancellation

**Via Admin:**

1. Ga naar Supplier Orders
2. Open een order die "pending" of "submitted" is
3. Klik "Cancel" actie
4. Vul cancellation reason in
5. Confirm

**Wat gebeurt:**
- ✅ Status wordt "cancelled"
- ✅ Cancellation fee berekend (0%, 10%, 50%, of 100%)
- ✅ Supplier API aangeroepen (als external_order_id bestaat)
- ✅ Refund automatisch verwerkt (als customer initiated)
- ✅ Activity log entry

**Check cancellation fee logic:**
- Pending/Artwork Ready: 0% fee
- Approved/Submitted: 10% fee
- Processing: 50% fee
- Later stages: 100% fee

### Stap 7: Test Issue Reporting

**Via Tinker:**

```bash
php artisan tinker
```

```php
use Lunar\Services\SupplierIssueService;
use Lunar\Models\SupplierOrder;

$service = app(SupplierIssueService::class);
$supplierOrder = SupplierOrder::first();
$user = \App\Models\User::first();

// Report issue
$issue = $service->reportIssue(
    supplierOrder: $supplierOrder,
    user: $user,
    type: 'quality',
    description: 'Print quality is poor, colors are off',
    severity: 'high',
    images: ['path/to/image.jpg']
);

// Acknowledge issue
$service->acknowledgeIssue($issue, $user);

// Resolve with reprint
$service->resolveIssue(
    issue: $issue,
    user: $user,
    resolution: 'reprint',
    compensationAmount: null,
    customerNotes: 'We will send a replacement'
);

// Request reprint order
$reprintOrder = $service->requestReprint($issue);
```

### Stap 8: Test Metrics & Analytics

**Via Tinker:**

```php
use Lunar\Services\SupplierMetricsService;

$metrics = app(SupplierMetricsService::class);

// Get supplier performance
$performance = $metrics->getSupplierPerformance(30);
dd($performance->toArray());

// Get profit margin report
$report = $metrics->getProfitMarginReport(30);
dd($report);

// Get supplier selection stats
$stats = $metrics->getSupplierSelectionStats(30);
dd($stats->toArray());

// Get orders by status
$statusCounts = $metrics->getOrdersByStatus();
dd($statusCounts);
```

### Stap 9: Test Customer Portal Features

**Via Tinker:**

```php
use Lunar\Services\OrderActivityService;
use Lunar\Models\Order;

$service = app(OrderActivityService::class);
$order = Order::first();

// Get order timeline (voor customer portal)
$timeline = $service->getOrderTimeline($order);
dd($timeline->toArray());

// Get order status summary
$status = $service->getOrderStatus($order);
dd($status);

// Check if customer can cancel
$canCancel = $service->canRequestCancellation($order);
dd($canCancel);

// Get cancellable orders with fees
$cancellable = $service->getCancellableOrders($order);
dd($cancellable->toArray());
```

## 🔍 Debug & Logging

### Activity Log bekijken

```php
// Get all activities for a supplier order
$supplierOrder = \Lunar\Models\SupplierOrder::first();
$activities = activity()
    ->forSubject($supplierOrder)
    ->get();

foreach ($activities as $activity) {
    echo $activity->description . "\n";
    echo "By: " . $activity->causer->name . "\n";
    echo "At: " . $activity->created_at . "\n";
    echo "Properties: " . json_encode($activity->properties) . "\n\n";
}
```

### Log Files

Check Laravel logs voor supplier API calls:
```bash
tail -f storage/logs/laravel.log | grep -i supplier
```

## 📊 Wat zie je in de Admin?

### Dashboard (na widget toevoegen)
- **Supplier Performance Table**
  - Orders per supplier (30d)
  - Delivery rates
  - Average delivery time
  - Profit margins
  - Issue counts

### Supplier Orders List
- **Kolommen**:
  - ID
  - Order reference
  - Supplier name
  - Status badge (kleuren per status)
  - Estimated cost
  - Revenue
  - Profit margin
  - Submitted at
  - Delivered at

- **Filters**:
  - Status dropdown
  - Supplier dropdown
  - Requires approval checkbox

- **Acties per row**:
  - View (👁️)
  - Edit (✏️)
  - Approve (✅) - alleen als requires_approval
  - Cancel (❌) - alleen als canBeCancelled()

### Supplier Order Detail Page
- **Order Information** sectie
- **Financial Details** sectie
- **Timeline** sectie
- **Selection Data** sectie (collapsible)
  - Score
  - Reason
  - Alternatives
  - Full quote data

## 🎨 Frontend Integratie (TODO)

Voor volledige customer portal heb je Livewire components nodig:

### Nog te bouwen:
1. **OrderTracking Component**
   - Gebruikt `OrderActivityService->getOrderTimeline()`
   - Toont progress bar
   - Toont timeline met events

2. **CancellationRequest Component**
   - Gebruikt `OrderActivityService->getCancellableOrders()`
   - Toont cancellation fees
   - Confirmation dialog

3. **OrderStatus Component**
   - Gebruikt `OrderActivityService->getOrderStatus()`
   - Toont current status
   - Progress percentage

## 🚀 Quick Start Commands

```bash
# Run migrations
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan config:clear

# Test supplier selection
php artisan tinker
>>> $service = app(\Lunar\Services\RuntimeSupplierSelector::class);
>>> $variant = \Lunar\Models\ProductVariant::first();
>>> $order = \Lunar\Models\Order::factory()->create();
>>> $orderLine = \Lunar\Models\OrderLine::factory()->create(['order_id' => $order->id]);
>>> $result = $service->selectBestSupplier($orderLine, $order);
>>> dd($result);
```

## 📝 Belangrijke Notes

1. **Supplier Priority**: Hogere waarde = meer kans om gekozen te worden (max 100)
2. **Cancellation Windows**: Configureer per supplier in capabilities
3. **Profit Margins**: Stel in per variant voor accurate tracking
4. **External Order IDs**: Worden automatisch gevuld bij submitOrder()
5. **Activity Logging**: Automatisch voor alle belangrijke acties

## 🐛 Troubleshooting

### "No suppliers available"
- Check of suppliers enabled zijn
- Check of supplier products gekoppeld zijn aan variants
- Check supplier capabilities

### "Cannot cancel order"
- Check cancellation_deadline
- Check order status (moet pending/approved/submitted zijn)
- Check supplier's allowed statuses in meta

### "Supplier selection always picks same supplier"
- Check priority weights
- Check if other suppliers have products configured
- Check external_data in supplier_orders table voor score details

## 📚 Documentatie Locaties

- **Plan**: `docs/plans/2026-01-12-multi-supplier-fulfillment-system.md`
- **Services**: `packages/core/src/Services/`
- **Models**: `packages/core/src/Models/`
- **Filament Resources**: `packages/admin/src/Filament/Resources/`
- **Tests**: `tests/core/Unit/`

## ✅ Checklist voor Production

- [ ] Configureer alle suppliers met juiste API keys
- [ ] Test cancellation flow end-to-end
- [ ] Test refund processing met test Stripe key
- [ ] Configureer email notifications
- [ ] Setup queue voor background jobs
- [ ] Monitor supplier API rate limits
- [ ] Setup alerts voor failed orders
- [ ] Test artwork approval workflow
- [ ] Document customer support procedures
- [ ] Setup automated reprint triggers
