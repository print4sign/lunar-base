# Print.com API - Complete Documentation

**Version:** v1.0
**Base URL (API):** `https://api.print.com/v1/`
**Base URL (Platform):** `https://platform.print.com/`
**Last Updated:** January 2026

---

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [API Architecture](#api-architecture)
4. [Common Headers](#common-headers)
5. [Error Handling](#error-handling)
6. [Rate Limits](#rate-limits)
7. [Endpoints](#endpoints)
   - [Products](#products)
   - [Pricing](#pricing)
   - [Orders](#orders)
   - [PDF Management](#pdf-management)
   - [Shipping](#shipping)
   - [Partners API](#partners-api)
8. [Data Models](#data-models)
9. [Webhooks & Callbacks](#webhooks--callbacks)
10. [Best Practices](#best-practices)
11. [Code Examples](#code-examples)

---

## Introduction

The Print.com API provides a comprehensive solution for integrating print-on-demand services into your application. This RESTful API enables:

- Browse and search products
- Get detailed product specifications
- Calculate real-time pricing
- Create and manage print orders
- Upload and process artwork (PDF)
- Track order status
- Manage shipping and fulfillment

**Access Requirements:**
- Active Print.com account
- API Key (request via Print.com support or dashboard)

**API Structure:**
Print.com offers three API tiers:
1. **Print.com API** - Core product and order management
2. **Print.com Platform API** - PDF processing and batch operations
3. **Print.com Partners API** - For print partners and fulfillment

---

## Authentication

### API Key Authentication

All API requests require authentication using an API key passed in the request headers.

**Header Name:** `X-API-Key`

**Example:**
```http
POST /products/batch/specs HTTP/1.1
Host: platform.print.com
X-API-Key: wLlF8Z4ovfCy9HK34oANWhnEbG15jG8YIvaEhOOw
Content-Type: application/json
```

**Security Best Practices:**
- Never expose API keys in client-side code
- Store keys securely in environment variables or secure vaults
- Rotate keys periodically
- Use HTTPS for all requests
- Different keys for test/production environments

**Error Response (401 Unauthorized):**
```json
{
  "message": "Invalid key=value pair (missing equal-sign) in Authorization header",
  "code": "UNAUTHORIZED"
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Access denied",
  "code": "FORBIDDEN"
}
```

---

## API Architecture

Print.com uses a multi-tier API architecture:

### 1. Print.com API (`api.print.com/v1`)
**Purpose:** Core operations for product browsing and order management

**Primary Endpoints:**
- Product catalog
- Order placement
- Order tracking
- Shipping calculations

**Use Case:** E-commerce integrations, customer-facing applications

### 2. Print.com Platform API (`platform.print.com`)
**Purpose:** Advanced operations and batch processing

**Primary Endpoints:**
- Batch product specifications
- Batch price calculations
- PDF processing (create, modify, preflight)
- PDF merging and preview

**Use Case:** High-volume operations, artwork processing, bulk integrations

### 3. Print.com Partners API
**Purpose:** For print partners managing fulfillment

**Primary Endpoints:**
- Order item management
- Shipment creation
- Label generation
- Status updates

**Use Case:** Print partners, fulfillment providers

---

## Common Headers

All requests should include these headers:

| Header | Required | Description | Example |
|--------|----------|-------------|---------|
| `X-API-Key` | Yes | Your API authentication key | `wLlF8Z4ovfCy9HK34oANWhnEbG15jG8YIvaEhOOw` |
| `Content-Type` | POST/PUT | Content type for request body | `application/json` |
| `Accept` | No | Expected response format | `application/json` |

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters or malformed JSON |
| 401 | Unauthorized | Missing or invalid API key |
| 403 | Forbidden | Access denied |
| 404 | Not Found | Resource or endpoint not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | API temporarily unavailable |

### Error Response Format

```json
{
  "message": "Detailed error message",
  "code": "ERROR_CODE",
  "details": {
    "field": "Additional context about the error"
  }
}
```

### Common Error Scenarios

**Invalid Product SKU:**
```json
{
  "specs": [
    {
      "error": "product with sku not found: invalid-sku"
    }
  ]
}
```

**Missing Required Fields:**
```json
{
  "message": "request body has an error: doesn't match schema",
  "details": "property \"options\" is missing"
}
```

**Validation Error:**
```json
{
  "message": "failed to validate batch request: field BatchGetProductSpecificationsRequest.Requests[0].Options is invalid, min: 1"
}
```

---

## Rate Limits

**Note:** Specific rate limits may vary based on your account tier.

**General Guidelines:**
- Implement exponential backoff for retries
- Use batch endpoints when processing multiple items
- Cache product data when possible
- Monitor 429 response codes

**Recommended Strategy:**
- Cache product specifications for 24 hours
- Cache pricing for 1-2 hours
- Batch requests when possible (use Platform API batch endpoints)

---

## Endpoints

### Products

#### Get All Products

Returns a list of all available products.

**Endpoint:** `GET /products`

**Base URL:** `https://api.print.com/v1`

**Parameters:** None required

**Response:**
```json
{
  "products": [
    {
      "sku": "business-cards-premium",
      "name": "Premium Business Cards",
      "description": "High-quality business cards on premium stock",
      "category": "Business Cards",
      "available": true,
      "basePrice": 19.99,
      "currency": "USD"
    },
    {
      "sku": "flyers-a5",
      "name": "A5 Flyers",
      "description": "Standard A5 promotional flyers",
      "category": "Flyers",
      "available": true,
      "basePrice": 29.99,
      "currency": "USD"
    }
  ],
  "total": 2,
  "page": 1,
  "perPage": 50
}
```

**Example Request:**
```bash
curl -X GET "https://api.print.com/v1/products" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

#### Get Product by SKU

Returns detailed information about a specific product.

**Endpoint:** `GET /products/{sku}`

**Base URL:** `https://api.print.com/v1`

**Path Parameters:**
- `sku` (string, required) - Product SKU identifier

**Response:**
```json
{
  "sku": "business-cards-premium",
  "name": "Premium Business Cards",
  "description": "Professional business cards on 350gsm cardstock",
  "category": "Business Cards",
  "images": [
    {
      "url": "https://cdn.print.com/products/bc-premium-1.jpg",
      "type": "preview",
      "width": 800,
      "height": 600
    }
  ],
  "specifications": {
    "material": "350gsm Cardstock",
    "finish": ["Matte", "Glossy", "Uncoated"],
    "sizes": ["85x55mm", "90x50mm"],
    "colors": ["4/4 CMYK", "4/0 CMYK"]
  },
  "options": [
    {
      "id": "finish",
      "name": "Finish",
      "type": "select",
      "required": true,
      "values": ["matte", "glossy", "uncoated"]
    },
    {
      "id": "quantity",
      "name": "Quantity",
      "type": "number",
      "required": true,
      "min": 50,
      "max": 10000,
      "step": 50
    }
  ],
  "requiresArtwork": true,
  "artworkSpecs": {
    "formats": ["PDF", "AI", "PSD"],
    "colorMode": "CMYK",
    "resolution": "300dpi",
    "bleed": "3mm"
  },
  "available": true
}
```

**Example Request:**
```bash
curl -X GET "https://api.print.com/v1/products/business-cards-premium" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

#### Get Product Accessories

Returns available accessories for a product (e.g., packaging, additional services).

**Endpoint:** `GET /products/{id}/accessories`

**Base URL:** `https://api.print.com/v1`

**Path Parameters:**
- `id` (string, required) - Product ID or SKU

**Response:**
```json
{
  "productId": "business-cards-premium",
  "accessories": [
    {
      "id": "acc-box-premium",
      "name": "Premium Gift Box",
      "description": "Elegant presentation box for business cards",
      "price": 4.99,
      "currency": "USD",
      "available": true
    },
    {
      "id": "acc-rounded-corners",
      "name": "Rounded Corners",
      "description": "Add rounded corners to your business cards",
      "price": 2.50,
      "currency": "USD",
      "available": true
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "https://api.print.com/v1/products/business-cards-premium/accessories" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

#### Batch Get Product Specifications

Returns detailed specifications for multiple products in a single request.

**Endpoint:** `POST /products/batch/specs`

**Base URL:** `https://platform.print.com`

**Request Body:**
```json
{
  "products": [
    {
      "sku": "business-cards-premium",
      "options": {
        "finish": "matte",
        "quantity": 500,
        "size": "85x55mm"
      }
    },
    {
      "sku": "flyers-a5",
      "options": {
        "paper": "170gsm-silk",
        "quantity": 1000,
        "sides": "double"
      }
    }
  ]
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `products` | array | Yes | Array of product specification queries |
| `products[].sku` | string | Yes | Product SKU identifier |
| `products[].options` | object | Yes | Product options (min: 1 field) |

**Response:**
```json
{
  "specs": [
    {
      "sku": "business-cards-premium",
      "specifications": {
        "dimensions": {
          "width": 85,
          "height": 55,
          "unit": "mm"
        },
        "material": "350gsm Cardstock",
        "finish": "Matte",
        "printArea": {
          "width": 79,
          "height": 49,
          "unit": "mm"
        },
        "bleed": 3,
        "safetyMargin": 5,
        "colorMode": "CMYK",
        "sides": "double"
      },
      "productionTime": {
        "min": 3,
        "max": 5,
        "unit": "business_days"
      },
      "artworkRequirements": {
        "fileFormat": ["PDF/X-1a", "PDF/X-3", "PDF/X-4"],
        "resolution": "300dpi",
        "colorProfile": "ISO Coated v2 (ECI)"
      }
    },
    {
      "sku": "flyers-a5",
      "error": "product with sku not found: flyers-a5"
    }
  ]
}
```

**Error Responses:**

Missing options field:
```json
{
  "message": "request body has an error: doesn't match schema #/components/schemas/BatchProductSpecsQueryObj: Error at \"/products/0/options\": property \"options\" is missing"
}
```

Empty options object:
```json
{
  "message": "failed to validate batch request: field BatchGetProductSpecificationsRequest.Requests[0].Options is invalid, min: 1"
}
```

**Example Request:**
```bash
curl -X POST "https://platform.print.com/products/batch/specs" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "products": [
      {
        "sku": "business-cards-premium",
        "options": {
          "finish": "matte",
          "quantity": 500
        }
      }
    ]
  }'
```

---

### Pricing

#### Calculate Product Price

Calculate pricing for a specific product configuration.

**Endpoint:** `POST /products/price`

**Base URL:** `https://api.print.com/v1`

**Request Body:**
```json
{
  "sku": "business-cards-premium",
  "quantity": 500,
  "options": {
    "finish": "matte",
    "size": "85x55mm",
    "corners": "rounded"
  },
  "shipping": {
    "country": "US",
    "zipCode": "10001",
    "serviceLevel": "standard"
  }
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `sku` | string | Yes | Product SKU |
| `quantity` | integer | Yes | Order quantity |
| `options` | object | No | Product configuration options |
| `shipping` | object | No | Shipping details for accurate pricing |
| `shipping.country` | string | No | ISO country code |
| `shipping.zipCode` | string | No | Postal/ZIP code |
| `shipping.serviceLevel` | string | No | Shipping service level |

**Response:**
```json
{
  "sku": "business-cards-premium",
  "quantity": 500,
  "pricing": {
    "unitPrice": 0.12,
    "subtotal": 60.00,
    "optionsUpcharge": 7.50,
    "productTotal": 67.50
  },
  "shipping": {
    "serviceLevel": "standard",
    "cost": 8.95,
    "estimatedDays": 5,
    "estimatedDelivery": "2026-01-20"
  },
  "summary": {
    "subtotal": 67.50,
    "shipping": 8.95,
    "tax": 6.12,
    "total": 82.57,
    "currency": "USD"
  },
  "priceBreakdown": [
    {
      "item": "Base Product (500 units)",
      "price": 60.00
    },
    {
      "item": "Matte Finish",
      "price": 5.00
    },
    {
      "item": "Rounded Corners",
      "price": 2.50
    }
  ]
}
```

**Example Request:**
```bash
curl -X POST "https://api.print.com/v1/products/price" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "sku": "business-cards-premium",
    "quantity": 500,
    "options": {
      "finish": "matte"
    }
  }'
```

---

#### Batch Calculate Prices

Calculate pricing for multiple products in a single request.

**Endpoint:** `POST /products/batch-prices`

**Base URL:** `https://platform.print.com`

**Request Body:**
```json
{
  "requests": [
    {
      "sku": "business-cards-premium",
      "quantity": 500,
      "options": {
        "finish": "matte"
      }
    },
    {
      "sku": "flyers-a5",
      "quantity": 1000,
      "options": {
        "paper": "170gsm-silk"
      }
    }
  ],
  "shipping": {
    "country": "US",
    "zipCode": "10001"
  }
}
```

**Response:**
```json
{
  "prices": [
    {
      "sku": "business-cards-premium",
      "quantity": 500,
      "total": 82.57,
      "currency": "USD"
    },
    {
      "sku": "flyers-a5",
      "quantity": 1000,
      "total": 145.99,
      "currency": "USD"
    }
  ],
  "combinedShipping": {
    "cost": 12.95,
    "savings": 4.95,
    "estimatedDays": 5
  },
  "grandTotal": {
    "subtotal": 215.06,
    "shipping": 12.95,
    "tax": 18.28,
    "total": 246.29,
    "currency": "USD"
  }
}
```

**Example Request:**
```bash
curl -X POST "https://platform.print.com/products/batch-prices" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "requests": [
      {
        "sku": "business-cards-premium",
        "quantity": 500,
        "options": {"finish": "matte"}
      }
    ]
  }'
```

---

### Orders

#### Place Order

Creates a new print order.

**Endpoint:** `POST /orders`

**Base URL:** `https://api.print.com/v1`

**Request Body:**
```json
{
  "orderReference": "ORDER-2026-001234",
  "customer": {
    "email": "customer@example.com",
    "name": "John Smith",
    "phone": "+1-555-0123"
  },
  "shipping": {
    "method": "standard",
    "address": {
      "name": "John Smith",
      "company": "Acme Corporation",
      "addressLine1": "123 Main Street",
      "addressLine2": "Suite 400",
      "city": "New York",
      "state": "NY",
      "zipCode": "10001",
      "country": "US",
      "phone": "+1-555-0123"
    }
  },
  "items": [
    {
      "sku": "business-cards-premium",
      "quantity": 500,
      "options": {
        "finish": "matte",
        "size": "85x55mm"
      },
      "artwork": {
        "url": "https://your-domain.com/uploads/artwork-123.pdf",
        "filename": "business-cards-design.pdf",
        "fileSize": 2048576,
        "pages": 1
      },
      "itemReference": "ITEM-001"
    }
  ],
  "billing": {
    "sameAsShipping": true
  },
  "metadata": {
    "source": "website",
    "notes": "Please handle with care"
  }
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `orderReference` | string | Yes | Your internal order reference |
| `customer` | object | Yes | Customer information |
| `customer.email` | string | Yes | Customer email |
| `customer.name` | string | Yes | Customer name |
| `customer.phone` | string | No | Customer phone |
| `shipping` | object | Yes | Shipping information |
| `shipping.method` | string | Yes | Shipping method (standard, express) |
| `shipping.address` | object | Yes | Shipping address |
| `items` | array | Yes | Array of order items |
| `items[].sku` | string | Yes | Product SKU |
| `items[].quantity` | integer | Yes | Order quantity |
| `items[].options` | object | No | Product options |
| `items[].artwork` | object | Yes | Artwork file information |
| `items[].itemReference` | string | No | Your line item reference |
| `billing` | object | No | Billing information |
| `metadata` | object | No | Additional order metadata |

**Response:**
```json
{
  "orderId": "PC-2026-001234",
  "orderReference": "ORDER-2026-001234",
  "status": "PENDING_ARTWORK_APPROVAL",
  "createdAt": "2026-01-12T15:00:00Z",
  "estimatedDelivery": "2026-01-22",
  "items": [
    {
      "itemId": "item_abc123",
      "itemReference": "ITEM-001",
      "sku": "business-cards-premium",
      "status": "ARTWORK_UPLOADED",
      "quantity": 500,
      "pricing": {
        "unitPrice": 0.12,
        "total": 67.50
      }
    }
  ],
  "totals": {
    "subtotal": 67.50,
    "shipping": 8.95,
    "tax": 6.12,
    "total": 82.57,
    "currency": "USD"
  },
  "production": {
    "estimatedStart": "2026-01-14",
    "estimatedCompletion": "2026-01-18",
    "estimatedShipDate": "2026-01-19"
  }
}
```

**Example Request:**
```bash
curl -X POST "https://api.print.com/v1/orders" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d @order.json
```

---

#### Get Orders

Retrieves a list of orders.

**Endpoint:** `GET /orders`

**Base URL:** `https://api.print.com/v1`

**Query Parameters:**
- `page` (integer, optional) - Page number (default: 1)
- `perPage` (integer, optional) - Items per page (default: 50, max: 100)
- `status` (string, optional) - Filter by status
- `dateFrom` (string, optional) - Filter by date (ISO 8601)
- `dateTo` (string, optional) - Filter by date (ISO 8601)
- `orderReference` (string, optional) - Filter by your order reference

**Response:**
```json
{
  "orders": [
    {
      "orderId": "PC-2026-001234",
      "orderReference": "ORDER-2026-001234",
      "status": "IN_PRODUCTION",
      "createdAt": "2026-01-12T15:00:00Z",
      "total": 82.57,
      "currency": "USD",
      "items": 1,
      "customer": {
        "name": "John Smith",
        "email": "customer@example.com"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 50,
    "total": 1,
    "totalPages": 1
  }
}
```

**Example Request:**
```bash
curl -X GET "https://api.print.com/v1/orders?status=IN_PRODUCTION&page=1" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

#### Get Order by Order Number

Retrieves detailed information about a specific order.

**Endpoint:** `GET /orders/{orderNumber}`

**Base URL:** `https://api.print.com/v1`

**Path Parameters:**
- `orderNumber` (string, required) - Print.com order number or your order reference

**Response:**
```json
{
  "orderId": "PC-2026-001234",
  "orderReference": "ORDER-2026-001234",
  "status": "IN_PRODUCTION",
  "createdAt": "2026-01-12T15:00:00Z",
  "updatedAt": "2026-01-13T10:30:00Z",
  "customer": {
    "name": "John Smith",
    "email": "customer@example.com",
    "phone": "+1-555-0123"
  },
  "shipping": {
    "method": "standard",
    "address": {
      "name": "John Smith",
      "company": "Acme Corporation",
      "addressLine1": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "zipCode": "10001",
      "country": "US"
    },
    "tracking": null
  },
  "items": [
    {
      "itemId": "item_abc123",
      "sku": "business-cards-premium",
      "status": "IN_PRODUCTION",
      "quantity": 500,
      "options": {
        "finish": "matte",
        "size": "85x55mm"
      },
      "artwork": {
        "status": "APPROVED",
        "filename": "business-cards-design.pdf",
        "approvedAt": "2026-01-12T16:00:00Z"
      },
      "pricing": {
        "unitPrice": 0.12,
        "total": 67.50
      }
    }
  ],
  "totals": {
    "subtotal": 67.50,
    "shipping": 8.95,
    "tax": 6.12,
    "total": 82.57,
    "currency": "USD"
  },
  "timeline": [
    {
      "status": "ORDER_PLACED",
      "timestamp": "2026-01-12T15:00:00Z",
      "note": "Order successfully placed"
    },
    {
      "status": "ARTWORK_UPLOADED",
      "timestamp": "2026-01-12T15:00:05Z"
    },
    {
      "status": "ARTWORK_APPROVED",
      "timestamp": "2026-01-12T16:00:00Z",
      "note": "Artwork passed automated checks"
    },
    {
      "status": "IN_PRODUCTION",
      "timestamp": "2026-01-13T10:30:00Z"
    }
  ],
  "production": {
    "estimatedCompletion": "2026-01-18",
    "estimatedShipDate": "2026-01-19",
    "estimatedDelivery": "2026-01-22"
  }
}
```

**Example Request:**
```bash
curl -X GET "https://api.print.com/v1/orders/PC-2026-001234" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

#### Update Order

Updates an existing order (limited fields, only before production starts).

**Endpoint:** `PUT /orders/{id}`

**Base URL:** `https://api.print.com/v1`

**Path Parameters:**
- `id` (string, required) - Order ID

**Request Body:**
```json
{
  "shipping": {
    "address": {
      "addressLine1": "456 New Street",
      "city": "Boston",
      "state": "MA",
      "zipCode": "02101"
    }
  },
  "metadata": {
    "notes": "Updated delivery instructions"
  }
}
```

**Response:**
```json
{
  "orderId": "PC-2026-001234",
  "status": "PENDING_ARTWORK_APPROVAL",
  "updatedAt": "2026-01-12T16:00:00Z",
  "message": "Order updated successfully"
}
```

**Important Notes:**
- Orders can only be updated before production starts
- Once `IN_PRODUCTION` or later, most fields cannot be changed
- Contact support for urgent changes to orders in production

**Example Request:**
```bash
curl -X PUT "https://api.print.com/v1/orders/PC-2026-001234" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "metadata": {
      "notes": "Rush order - please expedite"
    }
  }'
```

---

### PDF Management

#### Create PDF

Generate a PDF from templates or design data.

**Endpoint:** `POST /pdf/create`

**Base URL:** `https://platform.print.com`

**Request Body:**
```json
{
  "template": "business-card-template-1",
  "data": {
    "name": "John Smith",
    "title": "CEO",
    "company": "Acme Corp",
    "email": "john@acme.com",
    "phone": "+1-555-0123",
    "website": "www.acme.com",
    "logo": "https://example.com/logo.png"
  },
  "options": {
    "colorMode": "CMYK",
    "resolution": 300,
    "bleed": 3,
    "format": "PDF/X-4"
  }
}
```

**Response:**
```json
{
  "pdfId": "pdf_abc123xyz",
  "status": "COMPLETED",
  "createdAt": "2026-01-12T15:00:00Z",
  "downloadUrl": "https://platform.print.com/pdf/downloads/pdf_abc123xyz",
  "previewUrl": "https://platform.print.com/pdf/previews/pdf_abc123xyz.jpg",
  "expiresAt": "2026-01-19T15:00:00Z",
  "fileSize": 2048576,
  "pages": 1,
  "dimensions": {
    "width": 85,
    "height": 55,
    "unit": "mm"
  }
}
```

**Example Request:**
```bash
curl -X POST "https://platform.print.com/pdf/create" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d @pdf-data.json
```

---

#### Modify PDF

Modify an existing PDF file.

**Endpoint:** `POST /pdf/modify`

**Base URL:** `https://platform.print.com`

**Request Body:**
```json
{
  "sourceUrl": "https://example.com/original.pdf",
  "modifications": [
    {
      "type": "rotate",
      "angle": 90,
      "pages": [1]
    },
    {
      "type": "crop",
      "x": 0,
      "y": 0,
      "width": 85,
      "height": 55,
      "unit": "mm",
      "pages": "all"
    },
    {
      "type": "addBleed",
      "bleed": 3,
      "unit": "mm"
    }
  ],
  "output": {
    "format": "PDF/X-4",
    "colorMode": "CMYK",
    "compression": "high"
  }
}
```

**Response:**
```json
{
  "jobId": "modify_xyz789",
  "status": "PROCESSING",
  "message": "PDF modification job queued",
  "estimatedCompletion": "2026-01-12T15:05:00Z",
  "statusUrl": "https://platform.print.com/pdf/modify?jobId=modify_xyz789"
}
```

**Get Modification Results:**

**Endpoint:** `GET /pdf/modify?jobId={jobId}`

**Response:**
```json
{
  "jobId": "modify_xyz789",
  "status": "COMPLETED",
  "completedAt": "2026-01-12T15:03:00Z",
  "result": {
    "downloadUrl": "https://platform.print.com/pdf/downloads/modified_xyz789.pdf",
    "fileSize": 1950000,
    "pages": 1
  }
}
```

**Example Request:**
```bash
curl -X POST "https://platform.print.com/pdf/modify" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceUrl": "https://example.com/file.pdf",
    "modifications": [{"type": "addBleed", "bleed": 3}]
  }'
```

---

#### Preflight PDF

Validate and automatically fix common PDF issues.

**Endpoint:** `POST /pdf/preflight`

**Base URL:** `https://platform.print.com`

**Request Body:**
```json
{
  "sourceUrl": "https://example.com/artwork.pdf",
  "profile": "print-ready",
  "autofix": true,
  "checks": [
    "colorMode",
    "resolution",
    "fonts",
    "bleed",
    "cropMarks",
    "transparency"
  ],
  "targetSpecs": {
    "colorMode": "CMYK",
    "resolution": 300,
    "bleed": 3,
    "format": "PDF/X-4"
  }
}
```

**Response:**
```json
{
  "jobId": "preflight_abc123",
  "status": "COMPLETED",
  "completedAt": "2026-01-12T15:02:00Z",
  "result": {
    "passed": false,
    "issues": [
      {
        "severity": "error",
        "code": "COLOR_MODE_RGB",
        "message": "Document contains RGB colors",
        "pages": [1],
        "fixed": true
      },
      {
        "severity": "warning",
        "code": "LOW_RESOLUTION_IMAGE",
        "message": "Image resolution below 300dpi",
        "pages": [1],
        "location": {
          "x": 10,
          "y": 20
        },
        "fixed": false
      },
      {
        "severity": "info",
        "code": "MISSING_BLEED",
        "message": "Document has no bleed",
        "fixed": true
      }
    ],
    "summary": {
      "errors": 1,
      "warnings": 1,
      "info": 1,
      "fixed": 2
    }
  },
  "original": {
    "downloadUrl": "https://platform.print.com/pdf/downloads/original_abc123.pdf",
    "fileSize": 2500000
  },
  "fixed": {
    "downloadUrl": "https://platform.print.com/pdf/downloads/fixed_abc123.pdf",
    "fileSize": 2300000
  }
}
```

**Example Request:**
```bash
curl -X POST "https://platform.print.com/pdf/preflight" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceUrl": "https://example.com/artwork.pdf",
    "autofix": true
  }'
```

---

#### Merge PDFs

Combine multiple PDF files into a single document.

**Endpoint:** `POST /pdf/merge`

**Base URL:** `https://platform.print.com`

**Request Body:**
```json
{
  "files": [
    {
      "url": "https://example.com/page1.pdf",
      "pages": [1]
    },
    {
      "url": "https://example.com/page2.pdf",
      "pages": [1, 2]
    },
    {
      "url": "https://example.com/back-cover.pdf",
      "pages": "all"
    }
  ],
  "output": {
    "filename": "merged-document.pdf",
    "format": "PDF/X-4",
    "compression": "medium"
  }
}
```

**Response:**
```json
{
  "jobId": "merge_xyz789",
  "status": "COMPLETED",
  "completedAt": "2026-01-12T15:04:00Z",
  "result": {
    "downloadUrl": "https://platform.print.com/pdf/downloads/merged-document.pdf",
    "fileSize": 4800000,
    "pages": 4
  }
}
```

**Example Request:**
```bash
curl -X POST "https://platform.print.com/pdf/merge" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d @merge-request.json
```

---

#### Generate PDF Preview

Generate preview images from a PDF.

**Endpoint:** `GET /pdf/preview`

**Base URL:** `https://platform.print.com`

**Query Parameters:**
- `pdfUrl` (string, required) - URL of the PDF file
- `page` (integer, optional) - Page number (default: 1)
- `width` (integer, optional) - Preview width in pixels (default: 800)
- `height` (integer, optional) - Preview height in pixels
- `format` (string, optional) - Image format: jpg, png, webp (default: jpg)
- `quality` (integer, optional) - JPEG quality 1-100 (default: 85)

**Response:**
```json
{
  "previewUrl": "https://platform.print.com/pdf/previews/abc123_page1.jpg",
  "page": 1,
  "totalPages": 1,
  "dimensions": {
    "width": 800,
    "height": 500
  },
  "format": "jpg",
  "fileSize": 125000
}
```

**Example Request:**
```bash
curl -X GET "https://platform.print.com/pdf/preview?pdfUrl=https://example.com/file.pdf&page=1&width=800" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

### Shipping

#### Calculate Shipping Possibilities

Calculate available shipping options and costs for an order.

**Endpoint:** `POST /shipping/shipping-possibilities`

**Base URL:** `https://api.print.com/v1`

**Request Body:**
```json
{
  "items": [
    {
      "sku": "business-cards-premium",
      "quantity": 500,
      "options": {
        "finish": "matte"
      }
    }
  ],
  "destination": {
    "country": "US",
    "state": "NY",
    "zipCode": "10001",
    "city": "New York"
  }
}
```

**Response:**
```json
{
  "shippingOptions": [
    {
      "serviceLevel": "standard",
      "carrier": "USPS",
      "name": "Standard Shipping",
      "cost": 8.95,
      "currency": "USD",
      "estimatedDays": 5,
      "estimatedDelivery": "2026-01-20",
      "tracking": true
    },
    {
      "serviceLevel": "express",
      "carrier": "FedEx",
      "name": "Express Shipping",
      "cost": 24.95,
      "currency": "USD",
      "estimatedDays": 2,
      "estimatedDelivery": "2026-01-15",
      "tracking": true
    },
    {
      "serviceLevel": "overnight",
      "carrier": "FedEx",
      "name": "Overnight Shipping",
      "cost": 45.00,
      "currency": "USD",
      "estimatedDays": 1,
      "estimatedDelivery": "2026-01-14",
      "tracking": true
    }
  ]
}
```

**Example Request:**
```bash
curl -X POST "https://api.print.com/v1/shipping/shipping-possibilities" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"sku": "business-cards-premium", "quantity": 500}],
    "destination": {"country": "US", "zipCode": "10001"}
  }'
```

---

#### Get Shippable Countries

Returns a list of countries where Print.com ships.

**Endpoint:** `GET /shipping/shippable-countries`

**Base URL:** `https://api.print.com/v1`

**Response:**
```json
{
  "countries": [
    {
      "code": "US",
      "name": "United States",
      "regions": ["Domestic"],
      "availableCarriers": ["USPS", "FedEx", "UPS"]
    },
    {
      "code": "CA",
      "name": "Canada",
      "regions": ["North America"],
      "availableCarriers": ["Canada Post", "FedEx", "UPS"]
    },
    {
      "code": "GB",
      "name": "United Kingdom",
      "regions": ["Europe"],
      "availableCarriers": ["Royal Mail", "DHL"]
    }
  ],
  "total": 50
}
```

**Example Request:**
```bash
curl -X GET "https://api.print.com/v1/shipping/shippable-countries" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

#### Calculate Combined Shipment Discount

Calculate shipping discounts when multiple items are shipped together.

**Endpoint:** `POST /shipping/combined-shipment`

**Base URL:** `https://api.print.com/v1`

**Request Body:**
```json
{
  "items": [
    {
      "sku": "business-cards-premium",
      "quantity": 500
    },
    {
      "sku": "flyers-a5",
      "quantity": 1000
    },
    {
      "sku": "brochures-a4",
      "quantity": 250
    }
  ],
  "destination": {
    "country": "US",
    "zipCode": "10001"
  },
  "serviceLevel": "standard"
}
```

**Response:**
```json
{
  "separateShipping": {
    "total": 26.85,
    "breakdown": [
      {"sku": "business-cards-premium", "cost": 8.95},
      {"sku": "flyers-a5", "cost": 12.95},
      {"sku": "brochures-a4", "cost": 4.95}
    ]
  },
  "combinedShipping": {
    "total": 16.95,
    "savings": 9.90,
    "discountPercentage": 37
  },
  "recommendation": "combined",
  "estimatedDelivery": "2026-01-20"
}
```

**Example Request:**
```bash
curl -X POST "https://api.print.com/v1/shipping/combined-shipment" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d @shipment-request.json
```

---

### Partners API

The Partners API is designed for print service providers and fulfillment partners.

#### List Order Items by Status

**Endpoint:** `GET /orders/items`

**Base URL:** `https://partners.print.com/v1` (Partners API)

**Query Parameters:**
- `status` (string, optional) - Filter by status
- `page` (integer, optional) - Page number
- `perPage` (integer, optional) - Items per page

**Response:**
```json
{
  "items": [
    {
      "itemId": "item_abc123",
      "orderId": "PC-2026-001234",
      "sku": "business-cards-premium",
      "status": "READY_FOR_PRODUCTION",
      "quantity": 500,
      "dueDate": "2026-01-18",
      "artwork": {
        "downloadUrl": "https://partners.print.com/artwork/item_abc123.pdf",
        "status": "APPROVED"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 50,
    "total": 1
  }
}
```

---

#### Post Shipment

Create a shipment for completed order items.

**Endpoint:** `POST /shipping/shipment`

**Base URL:** `https://partners.print.com/v1`

**Request Body:**
```json
{
  "orderItemIds": ["item_abc123"],
  "carrier": "FedEx",
  "trackingNumber": "1234567890",
  "shipDate": "2026-01-18",
  "serviceLevel": "standard"
}
```

**Response:**
```json
{
  "shipmentId": "ship_xyz789",
  "status": "SHIPPED",
  "trackingUrl": "https://fedex.com/track/1234567890",
  "items": ["item_abc123"],
  "shippedAt": "2026-01-18T14:00:00Z"
}
```

---

#### Get Shipping Label

Generate or retrieve a shipping label.

**Endpoint:** `GET /shipping/label`

**Base URL:** `https://partners.print.com/v1`

**Query Parameters:**
- `shipmentId` (string, required) - Shipment ID
- `format` (string, optional) - Label format: pdf, zpl, png (default: pdf)

**Response:**
```json
{
  "labelUrl": "https://partners.print.com/labels/ship_xyz789.pdf",
  "format": "pdf",
  "size": "4x6",
  "carrier": "FedEx",
  "trackingNumber": "1234567890"
}
```

---

## Data Models

### Address Object

Used for shipping and billing addresses.

**Schema:**

| Field | Type | Required | Max Length | Description |
|-------|------|----------|------------|-------------|
| `name` | string | Yes | 64 | Recipient name |
| `company` | string | No | 64 | Company name |
| `addressLine1` | string | Yes | 128 | Primary address line |
| `addressLine2` | string | No | 128 | Secondary address line |
| `city` | string | Yes | 64 | City name |
| `state` | string | No | 32 | State/Province code |
| `zipCode` | string | Yes | 16 | Postal/ZIP code |
| `country` | string | Yes | 2 | ISO 3166-1 alpha-2 country code |
| `phone` | string | No | 32 | Phone number |

**Example:**
```json
{
  "name": "John Smith",
  "company": "Acme Corporation",
  "addressLine1": "123 Main Street",
  "addressLine2": "Suite 400",
  "city": "New York",
  "state": "NY",
  "zipCode": "10001",
  "country": "US",
  "phone": "+1-555-0123"
}
```

---

### Artwork Object

Describes artwork file requirements and status.

**Schema:**
```json
{
  "url": "string (required)",
  "filename": "string (optional)",
  "fileSize": "integer (bytes, optional)",
  "pages": "integer (optional)",
  "format": "string (optional)",
  "colorMode": "string (optional)",
  "status": "string (optional)"
}
```

**Artwork Status Values:**
- `UPLOADED` - File uploaded, awaiting validation
- `VALIDATING` - Automated validation in progress
- `APPROVED` - Passed all checks, ready for production
- `REJECTED` - Failed validation, requires fixes
- `MANUAL_REVIEW` - Flagged for manual review

---

### Product Option Object

Describes customizable product options.

**Schema:**
```json
{
  "id": "string",
  "name": "string",
  "type": "select|number|text|boolean",
  "required": true|false,
  "values": ["array of possible values"],
  "min": "number (for number type)",
  "max": "number (for number type)",
  "default": "any"
}
```

**Example:**
```json
{
  "id": "finish",
  "name": "Finish",
  "type": "select",
  "required": true,
  "values": ["matte", "glossy", "uncoated"],
  "default": "matte"
}
```

---

## Webhooks & Callbacks

Print.com can send webhooks to notify you about order status changes and other events.

### Setting Up Webhooks

Configure webhook URLs in your Print.com dashboard or via API (if supported).

**Recommended Webhook Events:**
- Order placed
- Artwork approved/rejected
- Production started
- Order shipped
- Delivery confirmed

### Webhook Payload Format

**Example - Order Status Update:**
```json
{
  "event": "order.status_changed",
  "timestamp": "2026-01-12T15:00:00Z",
  "data": {
    "orderId": "PC-2026-001234",
    "orderReference": "ORDER-2026-001234",
    "previousStatus": "PENDING_ARTWORK_APPROVAL",
    "currentStatus": "IN_PRODUCTION",
    "items": [
      {
        "itemId": "item_abc123",
        "status": "IN_PRODUCTION"
      }
    ]
  }
}
```

**Example - Artwork Status:**
```json
{
  "event": "artwork.status_changed",
  "timestamp": "2026-01-12T16:00:00Z",
  "data": {
    "orderId": "PC-2026-001234",
    "itemId": "item_abc123",
    "artworkStatus": "APPROVED",
    "message": "Artwork passed automated checks"
  }
}
```

**Example - Order Shipped:**
```json
{
  "event": "order.shipped",
  "timestamp": "2026-01-18T14:00:00Z",
  "data": {
    "orderId": "PC-2026-001234",
    "shipmentId": "ship_xyz789",
    "carrier": "FedEx",
    "trackingNumber": "1234567890",
    "trackingUrl": "https://fedex.com/track/1234567890",
    "estimatedDelivery": "2026-01-22"
  }
}
```

### Webhook Security

**Best Practices:**
1. Use HTTPS endpoints only
2. Validate webhook signatures (if provided)
3. Implement idempotency using event IDs
4. Return 200 OK quickly, process asynchronously
5. Log all webhook payloads
6. Implement retry logic for failed processing

**Response Requirements:**
- Return HTTP 200 within 5 seconds
- Return 200 even if processing fails (to prevent retries)

---

## Best Practices

### 1. Product Catalog Management

**Caching Strategy:**
- Cache product catalog for 24 hours
- Cache product specifications for 12 hours
- Invalidate cache when products are updated

**Example (PHP):**
```php
function getCachedProductSpecs($sku, $options) {
    $cacheKey = 'printcom_specs_' . md5($sku . json_encode($options));
    $cached = get_transient($cacheKey);

    if ($cached === false) {
        $specs = $apiClient->getProductSpecs($sku, $options);
        set_transient($cacheKey, $specs, 12 * HOUR_IN_SECONDS);
        return $specs;
    }

    return $cached;
}
```

---

### 2. Batch Operations

**Use Batch Endpoints:**
Always use batch endpoints when processing multiple items:

```javascript
// Good: Single batch request
const specs = await api.batchGetSpecs([
  {sku: 'bc-premium', options: {finish: 'matte', quantity: 500}},
  {sku: 'flyers-a5', options: {paper: '170gsm', quantity: 1000}}
]);

// Bad: Multiple separate requests
const spec1 = await api.getProductSpecs('bc-premium', {...});
const spec2 = await api.getProductSpecs('flyers-a5', {...});
```

---

### 3. Artwork Handling

**Artwork Requirements:**
- Format: PDF/X-1a, PDF/X-3, or PDF/X-4 preferred
- Color Mode: CMYK only
- Resolution: 300 DPI minimum
- Include: 3mm bleed on all sides
- Fonts: Embedded or outlined
- File Size: Under 100MB recommended

**Pre-upload Validation:**
```php
function validateArtwork($filePath) {
    // Check file size
    if (filesize($filePath) > 100 * 1024 * 1024) {
        return ['error' => 'File too large (max 100MB)'];
    }

    // Check PDF version
    $pdf = new PDFInfo($filePath);
    if (!in_array($pdf->version, ['PDF/X-1a', 'PDF/X-3', 'PDF/X-4'])) {
        return ['warning' => 'Not a PDF/X format'];
    }

    return ['status' => 'ok'];
}
```

---

### 4. Error Handling & Retries

**Retry Strategy:**
```javascript
async function callApiWithRetry(apiCall, maxRetries = 3) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await apiCall();
        } catch (error) {
            if (error.status === 429) {
                // Rate limit - exponential backoff
                await sleep(1000 * Math.pow(2, attempt));
            } else if (error.status >= 500) {
                // Server error - retry with delay
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

---

### 5. Price Calculation

**Get Accurate Pricing:**
Always include shipping destination for accurate pricing:

```json
{
  "sku": "business-cards-premium",
  "quantity": 500,
  "options": {"finish": "matte"},
  "shipping": {
    "country": "US",
    "zipCode": "10001"
  }
}
```

**Cache Pricing:**
Cache price calculations for 1-2 hours:

```php
$cacheKey = "price_{$sku}_{$quantity}_" . md5(json_encode($options));
$price = cache()->remember($cacheKey, 7200, function() use ($api, $sku, $quantity, $options) {
    return $api->calculatePrice($sku, $quantity, $options);
});
```

---

### 6. Order Workflow

**Recommended Order Flow:**

```
1. Get product specifications
   ↓
2. Calculate pricing with shipping
   ↓
3. Validate artwork (preflight API)
   ↓
4. Place order
   ↓
5. Monitor order status via webhooks
   ↓
6. Handle shipping notifications
```

---

### 7. Testing

**Test Checklist:**
- [ ] Product catalog retrieval works
- [ ] Batch specifications work correctly
- [ ] Price calculations include all costs
- [ ] Artwork validation catches issues
- [ ] Orders are created successfully
- [ ] Webhooks are received and processed
- [ ] Error handling works properly
- [ ] Retry logic prevents failures

---

## Code Examples

### PHP Example: Complete Order Flow

```php
<?php

class PrintComClient {
    private $apiKey;
    private $baseUrl = 'https://api.print.com/v1/';
    private $platformUrl = 'https://platform.print.com/';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    private function request($method, $url, $data = null) {
        $ch = curl_init($url);

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($statusCode >= 400) {
            throw new Exception($decoded['message'] ?? 'API Error');
        }

        return $decoded;
    }

    // Get all products
    public function getProducts() {
        return $this->request('GET', $this->baseUrl . 'products');
    }

    // Get product by SKU
    public function getProduct($sku) {
        return $this->request('GET', $this->baseUrl . 'products/' . $sku);
    }

    // Batch get product specifications
    public function batchGetSpecs($products) {
        return $this->request('POST', $this->platformUrl . 'products/batch/specs', [
            'products' => $products
        ]);
    }

    // Calculate price
    public function calculatePrice($sku, $quantity, $options = [], $shipping = null) {
        $data = [
            'sku' => $sku,
            'quantity' => $quantity,
            'options' => $options
        ];

        if ($shipping) {
            $data['shipping'] = $shipping;
        }

        return $this->request('POST', $this->baseUrl . 'products/price', $data);
    }

    // Preflight PDF
    public function preflightPDF($pdfUrl, $autofix = true) {
        return $this->request('POST', $this->platformUrl . 'pdf/preflight', [
            'sourceUrl' => $pdfUrl,
            'autofix' => $autofix,
            'profile' => 'print-ready'
        ]);
    }

    // Place order
    public function placeOrder($orderData) {
        return $this->request('POST', $this->baseUrl . 'orders', $orderData);
    }

    // Get order
    public function getOrder($orderId) {
        return $this->request('GET', $this->baseUrl . 'orders/' . $orderId);
    }
}

// Usage Example
$client = new PrintComClient('YOUR_API_KEY');

// 1. Get product
$product = $client->getProduct('business-cards-premium');

// 2. Get specifications
$specs = $client->batchGetSpecs([
    [
        'sku' => 'business-cards-premium',
        'options' => [
            'finish' => 'matte',
            'quantity' => 500
        ]
    ]
]);

// 3. Calculate price
$price = $client->calculatePrice('business-cards-premium', 500, [
    'finish' => 'matte'
], [
    'country' => 'US',
    'zipCode' => '10001'
]);

echo "Total price: $" . $price['summary']['total'] . "\n";

// 4. Preflight artwork
$preflight = $client->preflightPDF('https://example.com/artwork.pdf', true);

if ($preflight['result']['passed']) {
    echo "Artwork is print-ready!\n";
} else {
    echo "Issues found: " . $preflight['result']['summary']['errors'] . " errors\n";
}

// 5. Place order
$order = $client->placeOrder([
    'orderReference' => 'ORDER-12345',
    'customer' => [
        'email' => 'customer@example.com',
        'name' => 'John Smith'
    ],
    'shipping' => [
        'method' => 'standard',
        'address' => [
            'name' => 'John Smith',
            'addressLine1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zipCode' => '10001',
            'country' => 'US'
        ]
    ],
    'items' => [
        [
            'sku' => 'business-cards-premium',
            'quantity' => 500,
            'options' => ['finish' => 'matte'],
            'artwork' => [
                'url' => $preflight['fixed']['downloadUrl'],
                'filename' => 'business-cards.pdf'
            ]
        ]
    ]
]);

echo "Order placed: " . $order['orderId'] . "\n";
echo "Estimated delivery: " . $order['estimatedDelivery'] . "\n";
```

---

### JavaScript Example: Frontend Integration

```javascript
class PrintComAPI {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseUrl = 'https://api.print.com/v1';
        this.platformUrl = 'https://platform.print.com';
    }

    async request(method, url, data = null) {
        const options = {
            method,
            headers: {
                'X-API-Key': this.apiKey,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }

        return response.json();
    }

    // Get products
    async getProducts() {
        return this.request('GET', `${this.baseUrl}/products`);
    }

    // Batch get specs
    async batchGetSpecs(products) {
        return this.request('POST', `${this.platformUrl}/products/batch/specs`, {
            products
        });
    }

    // Calculate price
    async calculatePrice(sku, quantity, options, shipping) {
        return this.request('POST', `${this.baseUrl}/products/price`, {
            sku,
            quantity,
            options,
            shipping
        });
    }

    // Place order
    async placeOrder(orderData) {
        return this.request('POST', `${this.baseUrl}/orders`, orderData);
    }
}

// Usage Example
const api = new PrintComAPI('YOUR_API_KEY');

// Product configurator
async function configureProduct() {
    const sku = 'business-cards-premium';
    const quantity = 500;
    const options = {
        finish: 'matte',
        size: '85x55mm'
    };

    // Get specs
    const specsResult = await api.batchGetSpecs([{
        sku,
        options: {...options, quantity}
    }]);

    const specs = specsResult.specs[0];
    console.log('Product specs:', specs);

    // Calculate price
    const price = await api.calculatePrice(sku, quantity, options, {
        country: 'US',
        zipCode: '10001'
    });

    console.log('Total price:', price.summary.total);

    // Display to user
    document.getElementById('price').textContent = `$${price.summary.total}`;
    document.getElementById('delivery').textContent = price.shipping.estimatedDelivery;
}

configureProduct();
```

---

### Python Example: Batch Processing

```python
import requests
import time
from typing import List, Dict

class PrintComAPI:
    def __init__(self, api_key: str):
        self.api_key = api_key
        self.base_url = 'https://api.print.com/v1'
        self.platform_url = 'https://platform.print.com'
        self.session = requests.Session()
        self.session.headers.update({
            'X-API-Key': api_key,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })

    def request(self, method: str, url: str, data: Dict = None):
        try:
            response = self.session.request(method, url, json=data)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.HTTPError as e:
            print(f"HTTP Error: {e}")
            print(f"Response: {response.text}")
            raise

    def get_products(self):
        return self.request('GET', f'{self.base_url}/products')

    def batch_get_specs(self, products: List[Dict]):
        return self.request('POST', f'{self.platform_url}/products/batch/specs', {
            'products': products
        })

    def calculate_price(self, sku: str, quantity: int, options: Dict, shipping: Dict = None):
        data = {
            'sku': sku,
            'quantity': quantity,
            'options': options
        }
        if shipping:
            data['shipping'] = shipping
        return self.request('POST', f'{self.base_url}/products/price', data)

    def place_order(self, order_data: Dict):
        return self.request('POST', f'{self.base_url}/orders', order_data)

# Batch order processing
def process_bulk_orders(api: PrintComAPI, orders: List[Dict]):
    results = []

    for i, order_data in enumerate(orders):
        try:
            print(f"Processing order {i+1}/{len(orders)}...")

            # Place order
            result = api.place_order(order_data)
            results.append({
                'success': True,
                'orderReference': order_data['orderReference'],
                'orderId': result['orderId'],
                'total': result['totals']['total']
            })

            print(f"✓ Order placed: {result['orderId']}")

            # Rate limiting
            time.sleep(1)

        except Exception as e:
            print(f"✗ Order failed: {order_data['orderReference']}")
            print(f"  Error: {str(e)}")
            results.append({
                'success': False,
                'orderReference': order_data['orderReference'],
                'error': str(e)
            })

    return results

# Usage
api = PrintComAPI('YOUR_API_KEY')

orders = [
    {
        'orderReference': 'BULK-001',
        'customer': {'email': 'customer1@example.com', 'name': 'Customer 1'},
        'shipping': {...},
        'items': [...]
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
| US | United States |
| CA | Canada |
| GB | United Kingdom |
| DE | Germany |
| FR | France |
| NL | Netherlands |
| BE | Belgium |
| ES | Spain |
| IT | Italy |
| AU | Australia |

### B. Order Status Values

| Status | Description | Can Cancel |
|--------|-------------|------------|
| `PENDING_ARTWORK_APPROVAL` | Awaiting artwork validation | Yes |
| `ARTWORK_UPLOADED` | Artwork uploaded, being validated | Yes |
| `ARTWORK_APPROVED` | Artwork approved, ready for production | Yes |
| `ARTWORK_REJECTED` | Artwork failed validation | Yes |
| `READY_FOR_PRODUCTION` | Ready to start production | Limited |
| `IN_PRODUCTION` | Currently being produced | No |
| `COMPLETED` | Production completed | No |
| `SHIPPED` | Order shipped | No |
| `DELIVERED` | Order delivered | No |
| `CANCELLED` | Order cancelled | - |
| `ERROR` | Error occurred | Contact Support |

### C. Artwork File Formats

| Format | Extension | Recommended | Max Size |
|--------|-----------|-------------|----------|
| PDF/X-1a | .pdf | ✓ | 100MB |
| PDF/X-3 | .pdf | ✓ | 100MB |
| PDF/X-4 | .pdf | ✓ | 100MB |
| Adobe Illustrator | .ai | - | 150MB |
| Photoshop | .psd | - | 200MB |

### D. Support & Resources

**Documentation:**
- Developer Portal: https://developer.print.com
- API Reference: https://developer.print.com/reference

**Support:**
- Email: api-support@print.com
- Response Time: 1-2 business days

**Rate Limits:**
- Contact support for specific account limits
- Implement exponential backoff for retries

---

## Changelog

### Version 1.0 - January 2026
- Initial comprehensive documentation
- All core endpoints documented
- Code examples in PHP, JavaScript, Python
- Best practices and error handling

---

## License

This documentation is provided for Print.com API customers. The API and its usage are subject to Print.com's Terms of Service.

**Document Version:** 1.0
**Last Updated:** January 12, 2026
**Compiled By:** API Integration Team
