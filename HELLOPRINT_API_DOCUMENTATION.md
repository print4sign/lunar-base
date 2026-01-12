# Helloprint API - Complete Documentation

**Version:** v1.1 (Beta)
**Base URL:** `https://api.helloprint.com/rest/v1/`
**Last Updated:** January 2026

---

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [API Modes (Test vs Production)](#api-modes)
4. [Common Headers](#common-headers)
5. [Error Handling](#error-handling)
6. [Rate Limits](#rate-limits)
7. [Endpoints](#endpoints)
   - [Categories](#categories)
   - [Products](#products)
   - [Quotes](#quotes)
   - [Orders](#orders)
   - [Identity](#identity)
8. [Data Models](#data-models)
9. [Callbacks & Webhooks](#callbacks--webhooks)
10. [Order States](#order-states)
11. [Best Practices](#best-practices)
12. [Code Examples](#code-examples)

---

## Introduction

The Helloprint API provides a comprehensive solution for placing print orders and retrieving product information. This RESTful API is designed for customers within Helloprint Connect and enables:

- Browse categories and products
- Get real-time pricing quotes
- Create and manage orders
- Track order status
- Upload artwork files
- Receive webhooks for order updates

**Access Requirements:**
- Active Helloprint Connect account
- API Key (request via support team)

---

## Authentication

### API Key Authentication

All API requests require authentication using an API key passed in the request headers.

**Header Name:** `x-api-key`

**Example:**
```http
GET /products HTTP/1.1
Host: api.helloprint.com/rest/v1
x-api-key: CDPGAFHDABEVEYLRKAGBWWPKCOMWESHL
x-api-source: wordpress-plugin-1.0
```

**Security Best Practices:**
- Never expose API keys in client-side code
- Store keys securely in environment variables or secure vaults
- Rotate keys periodically
- Use HTTPS for all requests

**Error Response (403 Forbidden):**
```json
{
  "error": "Invalid API key",
  "message": "The provided API key is invalid or expired"
}
```

---

## API Modes

Helloprint API supports two operational modes:

### Test Mode
- **Purpose:** Development and testing without creating real orders
- **Mode Parameter:** `"mode": "test"`
- **Behavior:**
  - Orders are not sent to production
  - No charges are made
  - Full order flow is simulated
  - Callbacks are sent to test URLs

### Production Mode
- **Purpose:** Live orders
- **Mode Parameter:** `"mode": "prod"`
- **Behavior:**
  - Real orders are created and charged
  - Products are manufactured and shipped
  - Callbacks are sent to production URLs

**Important:** Always test thoroughly in test mode before switching to production.

---

## Common Headers

All requests should include these headers:

| Header | Required | Description | Example |
|--------|----------|-------------|---------|
| `x-api-key` | Yes | Your API authentication key | `CDPGAFHDABEVEYLRKAGBWWPKCOMWESHL` |
| `x-api-source` | No | Identifies your platform/app | `wordpress-plugin-1.0` |
| `Content-Type` | POST/PUT | Content type for request body | `application/json` |
| `Accept` | No | Expected response format | `application/json` |

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters |
| 403 | Forbidden | Invalid or missing API key |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | API temporarily unavailable |

### Error Response Format

```json
{
  "error": "ValidationError",
  "message": "Invalid product variant",
  "details": {
    "variantKey": "This variant is not available",
    "quantity": "Minimum quantity is 50"
  },
  "requestId": "req_abc123xyz"
}
```

### Common Error Scenarios

**Invalid Variant:**
```json
{
  "status": "ERROR",
  "message": "Product variant not found",
  "variantKey": "invalid-sku-123",
  "requestId": "req_xyz789"
}
```

**Missing Required Fields:**
```json
{
  "error": "MissingField",
  "message": "Required field missing",
  "field": "shipping.firstName"
}
```

---

## Rate Limits

**Note:** Specific rate limits are not publicly documented. Contact Helloprint support for details.

**General Guidelines:**
- Implement exponential backoff for retries
- Cache product and category data when possible
- Batch operations where supported
- Monitor 429 response codes

---

## Endpoints

### Categories

#### Get All Categories

Returns a list of all available product categories.

**Endpoint:** `GET /categories`

**Parameters:** None

**Response:**
```json
{
  "categories": [
    {
      "id": "business-cards",
      "name": "Business Cards",
      "description": "Professional business cards in various formats",
      "productCount": 15,
      "order": 1
    },
    {
      "id": "flyers",
      "name": "Flyers",
      "description": "Promotional flyers and leaflets",
      "productCount": 24,
      "order": 2
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/categories" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Category Products

Returns all products within a specific category.

**Endpoint:** `GET /categories/{categoryId}`

**Path Parameters:**
- `categoryId` (string, required) - Category identifier

**Response:**
```json
{
  "categoryId": "business-cards",
  "name": "Business Cards",
  "products": [
    {
      "id": "business-cards-standard",
      "name": "Standard Business Cards",
      "description": "Classic 85x55mm business cards",
      "basePrice": 9.99,
      "currency": "EUR",
      "available": true
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/categories/business-cards" \
  -H "x-api-key: YOUR_API_KEY"
```

---

### Products

#### Get All Products

Returns all products grouped by category.

**Endpoint:** `GET /products`

**Query Parameters:**
- `categoryId` (string, optional) - Filter by category

**Response:**
```json
{
  "Business Cards": {
    "business-cards-standard": "Standard Business Cards",
    "business-cards-premium": "Premium Business Cards"
  },
  "Flyers": {
    "flyers-a5": "A5 Flyers",
    "flyers-a4": "A4 Flyers"
  }
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/products" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Product Details

Returns detailed information about a specific product including attributes, options, and availability.

**Endpoint:** `GET /products/{productId}`

**Path Parameters:**
- `productId` (string, required) - Product identifier

**Response:**
```json
{
  "id": "business-cards-standard",
  "name": "Standard Business Cards",
  "description": "Professional business cards on 350gsm cardstock",
  "previewImage": "https://cdn.helloprint.com/products/bc-standard-preview.jpg",
  "galleryImages": [
    "https://cdn.helloprint.com/products/bc-standard-1.jpg",
    "https://cdn.helloprint.com/products/bc-standard-2.jpg"
  ],
  "attributes": [
    {
      "id": "finish",
      "name": "Finish",
      "type": "select",
      "required": true,
      "options": [
        {
          "value": "matte",
          "label": "Matte",
          "image": "https://cdn.helloprint.com/finish-matte.jpg",
          "subText": "Elegant non-reflective finish",
          "priceModifier": 0
        },
        {
          "value": "glossy",
          "label": "Glossy",
          "image": "https://cdn.helloprint.com/finish-glossy.jpg",
          "subText": "Shiny, vibrant colors",
          "priceModifier": 2.50
        }
      ]
    },
    {
      "id": "corners",
      "name": "Corner Style",
      "type": "select",
      "required": true,
      "options": [
        {
          "value": "standard",
          "label": "Standard Corners",
          "priceModifier": 0
        },
        {
          "value": "rounded",
          "label": "Rounded Corners",
          "priceModifier": 3.00
        }
      ]
    }
  ],
  "options": [
    {
      "id": "width",
      "name": "Width",
      "type": "number",
      "unit": "mm",
      "min": 80,
      "max": 90,
      "default": 85,
      "step": 1
    },
    {
      "id": "height",
      "name": "Height",
      "type": "number",
      "unit": "mm",
      "min": 50,
      "max": 60,
      "default": 55,
      "step": 1
    }
  ],
  "destinationCountries": [
    {
      "code": "NL",
      "name": "Netherlands"
    },
    {
      "code": "BE",
      "name": "Belgium"
    },
    {
      "code": "DE",
      "name": "Germany"
    }
  ],
  "available": true,
  "requiresArtwork": true,
  "allowsDesignService": true
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/products/business-cards-standard" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Product Variants

Returns available variants for a product based on selected attributes and filters.

**Endpoint:** `GET /products/{productId}/variants`

**Path Parameters:**
- `productId` (string, required) - Product identifier

**Query Parameters:**
- `attributes` (object, optional) - Filter by attribute values
- `sku` (string, optional) - Filter by SKU
- `includeAvailableQtys` (boolean, optional) - Include quantity options
- `destinationCountryCode` (string, optional) - ISO country code
- `where` (string, optional) - Advanced filter query

**Example Query:**
```
/products/business-cards-standard/variants?attributes[finish]=matte&attributes[corners]=rounded&includeAvailableQtys=true&destinationCountryCode=NL
```

**Response:**
```json
{
  "variants": [
    {
      "variantKey": "bc-std-matte-rounded-85x55",
      "sku": "BC-STD-001",
      "attributes": {
        "finish": "matte",
        "corners": "rounded"
      },
      "dimensions": {
        "width": 85,
        "height": 55,
        "unit": "mm"
      },
      "available": true,
      "availableQuantities": [50, 100, 250, 500, 1000, 2500],
      "minQuantity": 50,
      "maxQuantity": 10000
    }
  ],
  "filteredAttributes": [
    {
      "id": "finish",
      "availableOptions": ["matte", "glossy"]
    },
    {
      "id": "corners",
      "availableOptions": ["standard", "rounded"]
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/products/business-cards-standard/variants?includeAvailableQtys=true" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Product Templates

Returns available artwork templates (PDF, InDesign) for a product.

**Endpoint:** `GET /products/{productId}/templates`

**Path Parameters:**
- `productId` (string, required) - Product identifier

**Response:**
```json
{
  "templates": [
    {
      "type": "pdf",
      "format": "85x55mm",
      "orientation": "horizontal",
      "name": "Business Card Template - Horizontal",
      "downloadUrl": "https://cdn.helloprint.com/templates/bc-85x55-h.pdf",
      "previewUrl": "https://cdn.helloprint.com/templates/bc-85x55-h-preview.jpg",
      "fileSize": 245760,
      "lastUpdated": "2025-11-15T10:30:00Z"
    },
    {
      "type": "indesign",
      "format": "85x55mm",
      "orientation": "horizontal",
      "name": "Business Card Template - InDesign",
      "downloadUrl": "https://cdn.helloprint.com/templates/bc-85x55-h.indd",
      "fileSize": 512000,
      "lastUpdated": "2025-11-15T10:30:00Z"
    }
  ],
  "specifications": {
    "bleed": "3mm",
    "safetyMargin": "5mm",
    "colorMode": "CMYK",
    "resolution": "300dpi",
    "fileFormats": ["PDF", "INDD", "AI", "PSD"]
  }
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/products/business-cards-standard/templates" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Product by SKU

Returns product variant information by SKU.

**Endpoint:** `GET /getitemsbysku` (Legacy endpoint)
**Recommended:** Use `GET /products/{productId}/variants?sku={sku}`

**Query Parameters:**
- `sku` (string, required) - Product SKU

**Response:**
```json
{
  "sku": "BC-STD-001",
  "productId": "business-cards-standard",
  "variantKey": "bc-std-matte-rounded-85x55",
  "name": "Standard Business Cards - Matte, Rounded",
  "available": true
}
```

---

### Quotes

#### Create Quote

Generates a price quote for specific product variants and quantities.

**Endpoint:** `POST /quotes` (or legacy `/createquote`)

**Request Body:**
```json
{
  "items": [
    {
      "variantKey": "bc-std-matte-rounded-85x55",
      "quantity": [50, 100, 250, 500],
      "serviceLevel": "standard",
      "options": {
        "width": 85,
        "height": 55
      }
    }
  ],
  "destinationCountryCode": "NL"
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `items` | array | Yes | Array of items to quote |
| `items[].variantKey` | string | Yes | Product variant identifier |
| `items[].quantity` | int\|array | Yes | Single quantity or array of quantities |
| `items[].serviceLevel` | string | Yes | Shipping service level (standard, express) |
| `items[].options` | object | No | Custom options (dimensions, etc.) |
| `destinationCountryCode` | string | No | ISO country code for shipping |

**Response:**
```json
{
  "quoteId": "quote_abc123",
  "createdAt": "2026-01-12T14:30:00Z",
  "validUntil": "2026-01-19T14:30:00Z",
  "currency": "EUR",
  "items": [
    {
      "variantKey": "bc-std-matte-rounded-85x55",
      "quantities": [
        {
          "quantity": 50,
          "pricePerUnit": 0.20,
          "totalPrice": 10.00,
          "serviceLevels": [
            {
              "serviceLevel": "standard",
              "price": 5.95,
              "deliveryTime": "5-7 business days",
              "estimatedDelivery": "2026-01-20"
            },
            {
              "serviceLevel": "express",
              "price": 12.95,
              "deliveryTime": "2-3 business days",
              "estimatedDelivery": "2026-01-15"
            }
          ]
        },
        {
          "quantity": 100,
          "pricePerUnit": 0.18,
          "totalPrice": 18.00,
          "serviceLevels": [
            {
              "serviceLevel": "standard",
              "price": 5.95,
              "deliveryTime": "5-7 business days"
            }
          ]
        }
      ]
    }
  ],
  "summary": {
    "subtotal": 10.00,
    "shipping": 5.95,
    "tax": 3.35,
    "total": 19.30
  }
}
```

**Example Request:**
```bash
curl -X POST "https://api.helloprint.com/rest/v1/quotes" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{
      "variantKey": "bc-std-matte-rounded-85x55",
      "quantity": [50, 100, 250],
      "serviceLevel": "standard"
    }],
    "destinationCountryCode": "NL"
  }'
```

---

### Orders

#### Create Order

Creates a new print order.

**Endpoint:** `POST /orders` (or legacy `/createorder`)

**Request Body:**
```json
{
  "mode": "test",
  "orderReferenceId": "WC-ORDER-12345",
  "shipping": {
    "companyName": "Acme Corporation",
    "firstName": "John",
    "lastName": "Smith",
    "addressLine1": "Main Street 123",
    "addressLine2": "Floor 2",
    "postcode": "1012AB",
    "city": "Amsterdam",
    "country": "NL",
    "phone": "+31612345678"
  },
  "orderItems": [
    {
      "variantKey": "bc-std-matte-rounded-85x55",
      "quantity": 500,
      "serviceLevel": "standard",
      "options": {
        "width": 85,
        "height": 55
      },
      "artworkFile": {
        "url": "https://your-domain.com/uploads/artwork-123.pdf",
        "filename": "business-cards-design.pdf",
        "fileSize": 2048576,
        "checksum": "a3d5e9f..."
      },
      "itemReferenceId": "LINE-ITEM-001"
    }
  ],
  "callbackUrls": {
    "orderCreated": "https://your-domain.com/webhooks/helloprint/order-created",
    "orderShipped": "https://your-domain.com/webhooks/helloprint/order-shipped",
    "orderError": "https://your-domain.com/webhooks/helloprint/order-error"
  },
  "metadata": {
    "customerEmail": "john@acmecorp.com",
    "orderNotes": "Please handle with care"
  }
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `mode` | string | Yes | `test` or `prod` |
| `orderReferenceId` | string | Yes | Your internal order reference |
| `shipping` | object | Yes | Shipping address (see Address Object) |
| `orderItems` | array | Yes | Array of order items |
| `orderItems[].variantKey` | string | Yes | Product variant key |
| `orderItems[].quantity` | integer | Yes | Order quantity |
| `orderItems[].serviceLevel` | string | Yes | Shipping service level |
| `orderItems[].options` | object | No | Custom options |
| `orderItems[].artworkFile` | object | Yes | Artwork file information |
| `orderItems[].itemReferenceId` | string | No | Your line item reference |
| `callbackUrls` | object | No | Webhook URLs for order events |
| `metadata` | object | No | Additional order metadata |

**Response:**
```json
{
  "requestId": "req_abc123xyz",
  "status": "ORDER_CREATED",
  "message": "Order successfully created",
  "orderId": "HP-2026-001234",
  "orderReferenceId": "WC-ORDER-12345",
  "createdAt": "2026-01-12T15:00:00Z",
  "estimatedDelivery": "2026-01-20",
  "items": [
    {
      "itemId": "item_xyz789",
      "itemReferenceId": "LINE-ITEM-001",
      "variantKey": "bc-std-matte-rounded-85x55",
      "status": "ARTWORK_RECEIVED",
      "quantity": 500,
      "totalPrice": 97.50
    }
  ],
  "summary": {
    "subtotal": 90.00,
    "shipping": 5.95,
    "tax": 20.15,
    "total": 116.10,
    "currency": "EUR"
  }
}
```

**Example Request:**
```bash
curl -X POST "https://api.helloprint.com/rest/v1/orders" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d @order.json
```

---

#### Get Order by Request ID

Retrieves order details using the request ID returned when creating an order.

**Endpoint:** `GET /orders` (or legacy `/getorderbyrequestid`)

**Query Parameters:**
- `requestId` (string, required) - Request ID from order creation

**Response:**
```json
{
  "requestId": "req_abc123xyz",
  "orderId": "HP-2026-001234",
  "orderReferenceId": "WC-ORDER-12345",
  "status": "IN_PRODUCTION",
  "createdAt": "2026-01-12T15:00:00Z",
  "updatedAt": "2026-01-13T10:30:00Z",
  "mode": "test",
  "shipping": {
    "firstName": "John",
    "lastName": "Smith",
    "addressLine1": "Main Street 123",
    "city": "Amsterdam",
    "country": "NL"
  },
  "items": [
    {
      "itemId": "item_xyz789",
      "status": "IN_PRODUCTION",
      "variantKey": "bc-std-matte-rounded-85x55",
      "quantity": 500,
      "artworkStatus": "ARTWORK_ACCEPTED",
      "estimatedDelivery": "2026-01-20"
    }
  ],
  "timeline": [
    {
      "status": "ORDER_CREATED",
      "timestamp": "2026-01-12T15:00:00Z"
    },
    {
      "status": "ARTWORK_RECEIVED",
      "timestamp": "2026-01-12T15:00:05Z"
    },
    {
      "status": "ARTWORK_ACCEPTED",
      "timestamp": "2026-01-12T16:30:00Z"
    },
    {
      "status": "IN_PRODUCTION",
      "timestamp": "2026-01-13T10:30:00Z"
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/orders?requestId=req_abc123xyz" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Order by Order Reference ID

Retrieves order details using your internal order reference ID.

**Endpoint:** `GET /orders` (or legacy `/getorderbyorderreferenceid`)

**Query Parameters:**
- `orderReferenceId` (string, required) - Your internal order reference

**Response:** Same format as Get Order by Request ID

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/orders?orderReferenceId=WC-ORDER-12345" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Get Order by Order ID

Retrieves order details using the Helloprint order ID.

**Endpoint:** `GET /orders/{orderId}` (or legacy `/getorderbyid`)

**Path Parameters:**
- `orderId` (string, required) - Helloprint order ID

**Response:** Same format as Get Order by Request ID

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/orders/HP-2026-001234" \
  -H "x-api-key: YOUR_API_KEY"
```

---

#### Cancel Order

Cancels an entire order.

**Endpoint:** `POST /orders/{orderId}/cancel` (or legacy `/cancelorderbyid`)

**Path Parameters:**
- `orderId` (string, required) - Helloprint order ID

**Request Body:**
```json
{
  "reason": "Customer requested cancellation",
  "refundRequested": true
}
```

**Response:**
```json
{
  "orderId": "HP-2026-001234",
  "status": "CANCELLED",
  "cancelledAt": "2026-01-12T16:00:00Z",
  "reason": "Customer requested cancellation",
  "refundStatus": "PENDING",
  "message": "Order successfully cancelled"
}
```

**Important Notes:**
- Orders can only be cancelled before production starts
- Once an order is in `IN_PRODUCTION` state or later, cancellation may not be possible
- Check order status before attempting cancellation

**Example Request:**
```bash
curl -X POST "https://api.helloprint.com/rest/v1/orders/HP-2026-001234/cancel" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Customer requested cancellation",
    "refundRequested": true
  }'
```

---

#### Cancel Order Item

Cancels a specific item within an order.

**Endpoint:** `POST /orders/items/{itemId}/cancel` (or legacy `/cancelorderbyitemid`)

**Path Parameters:**
- `itemId` (string, required) - Order item ID

**Request Body:**
```json
{
  "reason": "Wrong product selected",
  "refundRequested": true
}
```

**Response:**
```json
{
  "itemId": "item_xyz789",
  "orderId": "HP-2026-001234",
  "status": "CANCELLED",
  "cancelledAt": "2026-01-12T16:00:00Z",
  "reason": "Wrong product selected",
  "refundStatus": "PENDING",
  "message": "Order item successfully cancelled"
}
```

**Example Request:**
```bash
curl -X POST "https://api.helloprint.com/rest/v1/orders/items/item_xyz789/cancel" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Wrong product selected"
  }'
```

---

### Identity

#### Validate API Key

Validates the API key and returns account information.

**Endpoint:** `GET /identity`

**Query Parameters:**
- `src` (string, optional) - Source platform identifier

**Response:**
```json
{
  "valid": true,
  "accountId": "acc_12345",
  "companyName": "Acme Corporation",
  "email": "api@acmecorp.com",
  "mode": "test",
  "permissions": [
    "orders:create",
    "orders:read",
    "orders:cancel",
    "products:read",
    "quotes:create"
  ],
  "rateLimit": {
    "requestsPerMinute": 60,
    "requestsPerHour": 1000
  }
}
```

**Example Request:**
```bash
curl -X GET "https://api.helloprint.com/rest/v1/identity?src=wordpress" \
  -H "x-api-key: YOUR_API_KEY"
```

---

## Data Models

### Address Object

Used for shipping addresses in orders.

**Schema:**

| Field | Type | Required | Max Length | Validation | Description |
|-------|------|----------|------------|------------|-------------|
| `companyName` | string\|null | No | 64 | - | Company name |
| `firstName` | string | Yes | 32 | - | First name |
| `lastName` | string | Yes | 32 | - | Last name |
| `addressLine1` | string | Yes | 128 | - | Primary address line |
| `addressLine2` | string | No | 128 | - | Secondary address line |
| `postcode` | string | Yes | 12 | `/^[a-zA-Z 0-9-]+$/` | Postal code |
| `city` | string | Yes | 64 | - | City name |
| `country` | string | Yes | 2 | ISO 3166-1 alpha-2 | Country code |
| `phone` | string | Yes | 32 | - | Phone number (normalized format) |

**Example:**
```json
{
  "companyName": "Acme Corporation",
  "firstName": "John",
  "lastName": "Smith",
  "addressLine1": "Main Street 123",
  "addressLine2": "Floor 2, Office 5",
  "postcode": "1012AB",
  "city": "Amsterdam",
  "country": "NL",
  "phone": "+31612345678"
}
```

**Phone Number Format:**
- Recommended format: International format with country code
- Example: `+31612345678` (Netherlands)
- Avoid spaces and special characters except `+` and `-`

**Country Codes:**
Common ISO 3166-1 alpha-2 codes:
- `NL` - Netherlands
- `BE` - Belgium
- `DE` - Germany
- `FR` - France
- `GB` - United Kingdom
- `US` - United States

---

### Product Attribute Object

Describes customizable product attributes.

**Schema:**
```json
{
  "id": "string",
  "name": "string",
  "type": "select|multiselect|number|text",
  "required": true|false,
  "options": [
    {
      "value": "string",
      "label": "string",
      "image": "url|null",
      "subText": "string|null",
      "priceModifier": 0.00
    }
  ]
}
```

---

### Order Item Object

Describes an item within an order.

**Schema:**
```json
{
  "variantKey": "string",
  "quantity": 500,
  "serviceLevel": "standard|express",
  "options": {
    "customOption1": "value",
    "customOption2": 123
  },
  "artworkFile": {
    "url": "https://...",
    "filename": "design.pdf",
    "fileSize": 2048576,
    "checksum": "sha256:..."
  },
  "itemReferenceId": "string"
}
```

---

## Callbacks & Webhooks

Helloprint sends webhooks to notify you about order status changes.

### Setting Up Callbacks

Specify callback URLs when creating an order:

```json
{
  "callbackUrls": {
    "orderCreated": "https://your-domain.com/webhooks/order-created",
    "orderShipped": "https://your-domain.com/webhooks/order-shipped",
    "orderError": "https://your-domain.com/webhooks/order-error"
  }
}
```

### Callback Events

#### Order Created

Sent when an order is successfully created.

**Webhook Payload:**
```json
{
  "event": "order.created",
  "timestamp": "2026-01-12T15:00:00Z",
  "requestId": "req_abc123xyz",
  "orderId": "HP-2026-001234",
  "orderReferenceId": "WC-ORDER-12345",
  "status": "ORDER_CREATED",
  "message": "Order Created",
  "items": [
    {
      "itemId": "item_xyz789",
      "status": "ARTWORK_RECEIVED"
    }
  ]
}
```

#### Order Shipped

Sent when one or more items in the order are shipped.

**Webhook Payload:**
```json
{
  "event": "order.shipped",
  "timestamp": "2026-01-18T14:30:00Z",
  "requestId": "req_abc123xyz",
  "orderId": "HP-2026-001234",
  "orderReferenceId": "WC-ORDER-12345",
  "status": "SHIPPED",
  "message": "Order Shipped",
  "items": [
    {
      "itemId": "item_xyz789",
      "status": "SHIPPED",
      "trackingNumber": "3SABCD1234567890",
      "trackingUrl": "https://tracking.carrier.com/track/3SABCD1234567890",
      "carrier": "DHL",
      "shippedAt": "2026-01-18T14:30:00Z",
      "estimatedDelivery": "2026-01-20"
    }
  ]
}
```

#### Order Error

Sent when an error occurs during order processing.

**Webhook Payload:**
```json
{
  "event": "order.error",
  "timestamp": "2026-01-12T15:05:00Z",
  "requestId": "req_abc123xyz",
  "orderId": "HP-2026-001234",
  "orderReferenceId": "WC-ORDER-12345",
  "status": "ERROR",
  "message": "Invalid product variant",
  "error": {
    "code": "INVALID_VARIANT",
    "message": "Product variant not found",
    "details": {
      "variantKey": "invalid-sku-123",
      "serviceLevel": "standard",
      "quantity": 500
    }
  }
}
```

### Webhook Security

**Best Practices:**
1. Validate webhook origin (check IP allowlist if provided)
2. Use HTTPS endpoints only
3. Verify webhook signatures (if provided by Helloprint)
4. Implement idempotency using `requestId` to prevent duplicate processing
5. Return 200 OK quickly, process asynchronously
6. Implement retry logic for failed processing

**Response Requirements:**
- Return HTTP 200 within 5 seconds
- Return 200 even if processing fails internally (to prevent retries)
- Log all webhook payloads for debugging

**Example Webhook Handler (PHP):**
```php
// webhook-handler.php
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Return 200 immediately
http_response_code(200);

// Process asynchronously
if ($data['event'] === 'order.shipped') {
    // Update order status in your database
    // Send shipping confirmation email
    // etc.
}
```

---

## Order States

Orders progress through various states during their lifecycle.

### State Diagram

```
ORDER_CREATED
    ↓
ARTWORK_RECEIVED
    ↓
ARTWORK_FILECHECK
    ↓ (automatic or manual review)
    ├─→ ARTWORK_ACCEPTED → READY_FOR_PRODUCTION
    └─→ ARTWORK_REJECTED → ARTWORK_APPROVAL_REQUIRED
            ↓
        (customer fixes and resubmits)
            ↓
        ARTWORK_RECEIVED (loop)

READY_FOR_PRODUCTION
    ↓
IN_PRODUCTION
    ↓
PACKAGED
    ↓
SHIPPED
    ↓
OUT_FOR_DELIVERY
    ↓ (can branch to)
    ├─→ DELIVERED
    ├─→ DELIVERED_AT_PICKUP_POINT
    ├─→ DELIVERED_AT_NEIGHBOURS
    └─→ DELIVERY_ATTEMPT_FAILED

(parallel states)
CARRIER_UPDATE_AVAILABLE
INVOICE_READY

(error states)
ERROR
CANCELLED
```

### State Descriptions

| State | Description | Actions Available | Next States |
|-------|-------------|-------------------|-------------|
| `ORDER_CREATED` | Order successfully created | Cancel order | `ARTWORK_RECEIVED` |
| `ARTWORK_RECEIVED` | Artwork file received | Cancel order | `ARTWORK_FILECHECK` |
| `ARTWORK_FILECHECK` | Artwork being validated | Cancel order | `ARTWORK_ACCEPTED`, `ARTWORK_REJECTED` |
| `ARTWORK_ACCEPTED` | Artwork approved | Cancel order (limited) | `READY_FOR_PRODUCTION` |
| `ARTWORK_REJECTED` | Artwork has issues | Upload new artwork | `ARTWORK_APPROVAL_REQUIRED` |
| `ARTWORK_APPROVAL_REQUIRED` | Waiting for customer action | Upload corrected artwork | `ARTWORK_RECEIVED` |
| `ARTWORK_REQUIRED` | No artwork uploaded | Upload artwork | `ARTWORK_RECEIVED` |
| `READY_FOR_PRODUCTION` | Ready to start production | Cancel order (limited) | `IN_PRODUCTION` |
| `IN_PRODUCTION` | Currently being printed | - | `PACKAGED` |
| `PACKAGED` | Packaged and ready to ship | - | `SHIPPED` |
| `SHIPPED` | Handed to carrier | Track shipment | `OUT_FOR_DELIVERY` |
| `OUT_FOR_DELIVERY` | Out for delivery | Track shipment | `DELIVERED`, etc. |
| `DELIVERED` | Successfully delivered | - | - |
| `DELIVERED_AT_PICKUP_POINT` | Delivered to pickup point | - | - |
| `DELIVERED_AT_NEIGHBOURS` | Delivered to neighbor | - | - |
| `DELIVERY_ATTEMPT_FAILED` | Delivery attempt failed | - | `OUT_FOR_DELIVERY` (retry) |
| `CARRIER_UPDATE_AVAILABLE` | Carrier tracking updated | Check tracking | - |
| `INVOICE_READY` | Invoice generated | Download invoice | - |
| `CANCELLED` | Order cancelled | - | - |
| `ERROR` | Error occurred | Contact support | - |

### State Transitions

**Cancellation Window:**
- `ORDER_CREATED` to `ARTWORK_ACCEPTED`: Full cancellation available
- `READY_FOR_PRODUCTION` to early `IN_PRODUCTION`: Cancellation may be possible (contact support)
- `IN_PRODUCTION` (late) onwards: Cancellation typically not possible

**Artwork Rejection Flow:**
If artwork is rejected:
1. Order moves to `ARTWORK_APPROVAL_REQUIRED`
2. Customer receives notification with rejection reasons
3. Customer uploads corrected artwork
4. Artwork goes through validation again

---

## Best Practices

### 1. Product Catalog Management

**Caching Strategy:**
- Cache product catalog locally (categories, products, attributes)
- Refresh cache daily or when products are updated
- Cache duration: 24 hours for categories, 12 hours for product details

**Example (PHP):**
```php
function getCachedProducts($apiService) {
    $cacheKey = 'helloprint_products';
    $cached = get_transient($cacheKey);

    if ($cached === false) {
        $products = $apiService->getAllProducts();
        set_transient($cacheKey, $products, 12 * HOUR_IN_SECONDS);
        return $products;
    }

    return $cached;
}
```

### 2. Price Quote Handling

**Request Batching:**
- Batch multiple quantity options in single quote request
- Example: Request [50, 100, 250, 500] instead of 4 separate requests

**Cache Quotes:**
- Cache quotes for 1-2 hours (prices change infrequently)
- Invalidate cache when product attributes change

### 3. Order Creation

**Pre-flight Validation:**
```php
// Validate before creating order
1. Validate address format
2. Verify product variant exists
3. Confirm quantity is available
4. Ensure artwork file is accessible
5. Test mode first, then production
```

**Artwork File Requirements:**
- Format: PDF preferred (also accepts AI, PSD, INDD)
- Color Mode: CMYK
- Resolution: 300 DPI minimum
- File Size: Under 100MB recommended
- Include: Bleed (3mm) and crop marks
- URL: Must be publicly accessible (HTTPS recommended)

**Order Reference IDs:**
- Use unique identifiers (e.g., WooCommerce order ID)
- Include prefix for easy identification: `WC-12345`, `SHOP-67890`
- Store mapping between your ID and Helloprint's orderId

### 4. Error Handling

**Retry Strategy:**
```javascript
async function createOrderWithRetry(orderData, maxRetries = 3) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await createOrder(orderData);
        } catch (error) {
            if (error.status === 429) {
                // Rate limit - wait and retry
                await sleep(1000 * attempt);
            } else if (error.status >= 500) {
                // Server error - retry
                await sleep(2000 * attempt);
            } else {
                // Client error - don't retry
                throw error;
            }
        }
    }
    throw new Error('Max retries exceeded');
}
```

**Validation Errors:**
- Validate all data locally before API call
- Check address format, phone number, country code
- Verify variant exists and quantity is valid
- Ensure artwork file is accessible

### 5. Webhook Handling

**Idempotency:**
```php
function handleWebhook($payload) {
    $requestId = $payload['requestId'];

    // Check if already processed
    if (webhookAlreadyProcessed($requestId)) {
        return; // Already handled
    }

    // Process webhook
    processOrderUpdate($payload);

    // Mark as processed
    markWebhookProcessed($requestId);
}
```

**Async Processing:**
```php
// Return 200 immediately
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'received']);

// Close connection
fastcgi_finish_request(); // or similar

// Process asynchronously
processWebhookAsync($payload);
```

### 6. Testing

**Test Checklist:**
- [ ] Test mode orders don't charge
- [ ] Callbacks received correctly
- [ ] Order status updates reflect in your system
- [ ] Cancellations work as expected
- [ ] Error handling works properly
- [ ] Address validation catches issues
- [ ] Artwork upload succeeds
- [ ] Price calculations are correct

**Test Data:**
```json
{
  "mode": "test",
  "orderReferenceId": "TEST-12345",
  "shipping": {
    "firstName": "Test",
    "lastName": "User",
    "addressLine1": "Test Street 1",
    "postcode": "1234AB",
    "city": "Amsterdam",
    "country": "NL",
    "phone": "+31612345678"
  }
}
```

### 7. Performance Optimization

**Minimize API Calls:**
- Use variant filtering to get only needed data
- Batch operations where possible
- Implement smart caching
- Use webhooks instead of polling

**Example: Efficient Variant Filtering:**
```javascript
// Bad: Multiple separate calls
const variants1 = await getVariants(productId, {finish: 'matte'});
const variants2 = await getVariants(productId, {finish: 'glossy'});

// Good: Single call with filter
const allVariants = await getVariants(productId, {
    attributes: {finish: ['matte', 'glossy']},
    includeAvailableQtys: true
});
```

### 8. Security

**API Key Protection:**
- Store in environment variables
- Never commit to version control
- Use different keys for test/production
- Rotate keys periodically

**Webhook Security:**
- Use HTTPS only
- Validate webhook source
- Check request signatures (if provided)
- Rate limit webhook endpoints

### 9. Monitoring & Logging

**Log These Events:**
- All API requests (method, endpoint, response time)
- All API errors (status code, error message)
- Order creations (requestId, orderReferenceId)
- Webhook receipts (event type, timestamp)
- Failed operations (validation errors, network errors)

**Example Log Entry:**
```json
{
  "timestamp": "2026-01-12T15:00:00Z",
  "level": "INFO",
  "message": "Order created successfully",
  "context": {
    "requestId": "req_abc123xyz",
    "orderId": "HP-2026-001234",
    "orderReferenceId": "WC-12345",
    "responseTime": 1234
  }
}
```

---

## Code Examples

### PHP Example: Complete Order Flow

```php
<?php

class HelloprintClient {
    private $apiKey;
    private $baseUrl = 'https://api.helloprint.com/rest/v1/';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;

        $args = [
            'method' => $method,
            'timeout' => 45,
            'headers' => [
                'x-api-key' => $this->apiKey,
                'x-api-source' => 'custom-integration-1.0',
                'Content-Type' => 'application/json'
            ]
        ];

        if ($data !== null) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode >= 400) {
            throw new Exception('API error: ' . ($body['message'] ?? 'Unknown error'));
        }

        return $body;
    }

    // Get all products
    public function getProducts() {
        return $this->request('GET', 'products');
    }

    // Get product details
    public function getProductDetails($productId) {
        return $this->request('GET', 'products/' . $productId);
    }

    // Get product variants
    public function getProductVariants($productId, $filters = []) {
        $query = http_build_query($filters);
        $endpoint = 'products/' . $productId . '/variants';
        if ($query) {
            $endpoint .= '?' . $query;
        }
        return $this->request('GET', $endpoint);
    }

    // Create quote
    public function createQuote($items, $destinationCountry = null) {
        $data = ['items' => $items];
        if ($destinationCountry) {
            $data['destinationCountryCode'] = $destinationCountry;
        }
        return $this->request('POST', 'quotes', $data);
    }

    // Create order
    public function createOrder($orderData) {
        return $this->request('POST', 'orders', $orderData);
    }

    // Get order by request ID
    public function getOrderByRequestId($requestId) {
        return $this->request('GET', 'orders?requestId=' . $requestId);
    }

    // Cancel order
    public function cancelOrder($orderId, $reason = null) {
        $data = [];
        if ($reason) {
            $data['reason'] = $reason;
        }
        return $this->request('POST', 'orders/' . $orderId . '/cancel', $data);
    }
}

// Usage Example
$client = new HelloprintClient('YOUR_API_KEY');

// 1. Get products
$products = $client->getProducts();

// 2. Get product details
$productDetails = $client->getProductDetails('business-cards-standard');

// 3. Get variants with filters
$variants = $client->getProductVariants('business-cards-standard', [
    'attributes' => ['finish' => 'matte'],
    'includeAvailableQtys' => true,
    'destinationCountryCode' => 'NL'
]);

// 4. Create quote
$quote = $client->createQuote([
    [
        'variantKey' => 'bc-std-matte-rounded-85x55',
        'quantity' => [50, 100, 250],
        'serviceLevel' => 'standard'
    ]
], 'NL');

// 5. Create order
$order = $client->createOrder([
    'mode' => 'test',
    'orderReferenceId' => 'ORDER-12345',
    'shipping' => [
        'firstName' => 'John',
        'lastName' => 'Smith',
        'addressLine1' => 'Main Street 123',
        'postcode' => '1012AB',
        'city' => 'Amsterdam',
        'country' => 'NL',
        'phone' => '+31612345678'
    ],
    'orderItems' => [
        [
            'variantKey' => 'bc-std-matte-rounded-85x55',
            'quantity' => 500,
            'serviceLevel' => 'standard',
            'artworkFile' => [
                'url' => 'https://example.com/artwork.pdf',
                'filename' => 'business-cards.pdf',
                'fileSize' => 2048576
            ]
        ]
    ],
    'callbackUrls' => [
        'orderCreated' => 'https://example.com/webhooks/order-created',
        'orderShipped' => 'https://example.com/webhooks/order-shipped'
    ]
]);

echo "Order created: " . $order['orderId'] . "\n";
echo "Request ID: " . $order['requestId'] . "\n";
```

### JavaScript Example: Frontend Integration

```javascript
class HelloprintAPI {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseUrl = 'https://api.helloprint.com/rest/v1/';
    }

    async request(method, endpoint, data = null) {
        const options = {
            method,
            headers: {
                'x-api-key': this.apiKey,
                'x-api-source': 'web-app-1.0',
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(this.baseUrl + endpoint, options);

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }

        return response.json();
    }

    // Get products
    async getProducts() {
        return this.request('GET', 'products');
    }

    // Get product variants
    async getProductVariants(productId, filters = {}) {
        const params = new URLSearchParams(filters);
        const endpoint = `products/${productId}/variants?${params}`;
        return this.request('GET', endpoint);
    }

    // Create quote
    async createQuote(items, destinationCountry) {
        return this.request('POST', 'quotes', {
            items,
            destinationCountryCode: destinationCountry
        });
    }
}

// Usage Example
const api = new HelloprintAPI('YOUR_API_KEY');

// Product selector
async function loadProducts() {
    const products = await api.getProducts();

    // Render products in UI
    const productList = document.getElementById('product-list');
    for (const [category, items] of Object.entries(products)) {
        const categoryDiv = document.createElement('div');
        categoryDiv.innerHTML = `<h3>${category}</h3>`;

        for (const [id, name] of Object.entries(items)) {
            const productButton = document.createElement('button');
            productButton.textContent = name;
            productButton.onclick = () => selectProduct(id);
            categoryDiv.appendChild(productButton);
        }

        productList.appendChild(categoryDiv);
    }
}

// Variant filter with live pricing
async function updateVariants(productId, selectedAttributes) {
    const variants = await api.getProductVariants(productId, {
        attributes: selectedAttributes,
        includeAvailableQtys: true,
        destinationCountryCode: 'NL'
    });

    // Update available options in UI
    renderAvailableOptions(variants.filteredAttributes);

    // Get pricing
    if (variants.variants.length > 0) {
        const quote = await api.createQuote([{
            variantKey: variants.variants[0].variantKey,
            quantity: [50, 100, 250, 500],
            serviceLevel: 'standard'
        }], 'NL');

        renderPricing(quote);
    }
}
```

### Python Example: Batch Processing

```python
import requests
import time
from typing import List, Dict

class HelloprintAPI:
    def __init__(self, api_key: str):
        self.api_key = api_key
        self.base_url = 'https://api.helloprint.com/rest/v1/'
        self.session = requests.Session()
        self.session.headers.update({
            'x-api-key': api_key,
            'x-api-source': 'python-script-1.0',
            'Content-Type': 'application/json'
        })

    def request(self, method: str, endpoint: str, data: Dict = None):
        url = self.base_url + endpoint

        try:
            if method == 'GET':
                response = self.session.get(url, params=data)
            else:
                response = self.session.request(method, url, json=data)

            response.raise_for_status()
            return response.json()

        except requests.exceptions.HTTPError as e:
            print(f"HTTP Error: {e}")
            print(f"Response: {response.text}")
            raise

    def get_products(self):
        return self.request('GET', 'products')

    def create_order(self, order_data: Dict):
        return self.request('POST', 'orders', order_data)

    def get_order(self, request_id: str):
        return self.request('GET', f'orders?requestId={request_id}')

# Batch order processing
def process_bulk_orders(api: HelloprintAPI, orders: List[Dict]):
    results = []

    for i, order_data in enumerate(orders):
        try:
            print(f"Processing order {i+1}/{len(orders)}...")

            # Create order
            result = api.create_order(order_data)
            results.append({
                'success': True,
                'orderReferenceId': order_data['orderReferenceId'],
                'requestId': result['requestId'],
                'orderId': result['orderId']
            })

            print(f"✓ Order created: {result['orderId']}")

            # Rate limiting: wait between requests
            time.sleep(1)

        except Exception as e:
            print(f"✗ Order failed: {order_data['orderReferenceId']}")
            print(f"  Error: {str(e)}")
            results.append({
                'success': False,
                'orderReferenceId': order_data['orderReferenceId'],
                'error': str(e)
            })

    return results

# Usage
api = HelloprintAPI('YOUR_API_KEY')

orders = [
    {
        'mode': 'test',
        'orderReferenceId': 'BULK-001',
        'shipping': {...},
        'orderItems': [...]
    },
    # ... more orders
]

results = process_bulk_orders(api, orders)

# Summary
successful = sum(1 for r in results if r['success'])
failed = len(results) - successful
print(f"\nProcessed {len(results)} orders: {successful} successful, {failed} failed")
```

---

## Appendix

### A. Common Country Codes

| Code | Country |
|------|---------|
| NL | Netherlands |
| BE | Belgium |
| DE | Germany |
| FR | France |
| GB | United Kingdom |
| US | United States |
| ES | Spain |
| IT | Italy |
| AT | Austria |
| CH | Switzerland |
| DK | Denmark |
| SE | Sweden |
| NO | Norway |
| FI | Finland |
| PL | Poland |

### B. Service Levels

| Service Level | Description | Typical Delivery Time |
|--------------|-------------|----------------------|
| `standard` | Standard shipping | 5-7 business days |
| `express` | Express shipping | 2-3 business days |

### C. File Format Requirements

| Format | Extension | Max Size | Color Mode | Resolution |
|--------|-----------|----------|------------|------------|
| PDF | .pdf | 100MB | CMYK | 300 DPI |
| Adobe Illustrator | .ai | 100MB | CMYK | Vector/300 DPI |
| Photoshop | .psd | 150MB | CMYK | 300 DPI |
| InDesign | .indd | 150MB | CMYK | 300 DPI |

**Important Notes:**
- Include 3mm bleed on all sides
- Use CMYK color mode (not RGB)
- Embed or outline all fonts
- Flatten layers if possible
- Include crop marks

### D. Glossary

| Term | Definition |
|------|------------|
| **Variant** | A specific product configuration (e.g., matte finish, rounded corners) |
| **Variant Key** | Unique identifier for a product variant |
| **SKU** | Stock Keeping Unit - product identifier |
| **Service Level** | Shipping speed option (standard, express) |
| **Request ID** | Unique identifier returned when creating an order |
| **Order Reference ID** | Your internal order identifier |
| **Bleed** | Extra area around design edge (typically 3mm) |
| **CMYK** | Cyan, Magenta, Yellow, Key (black) - print color mode |

### E. Support & Resources

**Documentation:**
- Official API Docs: https://developers.helloprint.com/reference

**Support:**
- Email: Contact via Helloprint support
- Response Time: 1-2 business days

**API Status:**
- Check status page (if available) for outages
- Subscribe to status updates

**Rate Limits:**
- Contact support for specific limits
- Implement exponential backoff

**Feature Requests:**
- Submit via support email
- Include use case and expected behavior

---

## Changelog

### Version 1.1 (Beta) - Current
- Initial API documentation
- REST endpoints for products, quotes, orders
- Webhook support for order updates
- Test mode for development

---

## License

This documentation is provided for Helloprint API customers. The API and its usage are subject to Helloprint's Terms of Service.

**Document Version:** 1.0
**Last Updated:** January 12, 2026
**Author:** API Integration Team
