# 💳 Payment Module — Complete Documentation

A **plug-and-play**, modular payment system for Laravel applications. Drop this module into any Laravel project — LMS, eCommerce, Booking, SaaS — and get multi-gateway payment support with zero coupling.

---

## Table of Contents

- [Supported Gateways](#supported-gateways)
- [Architecture Overview](#architecture-overview)
- [Installation & Setup](#installation--setup)
  - [Step 1: Copy the Module](#step-1-copy-the-module)
  - [Step 2: Register the Module](#step-2-register-the-module)
  - [Step 3: Configure Environment Variables](#step-3-configure-environment-variables)
  - [Step 4: Table Prefix (Avoiding Conflicts)](#step-4-table-prefix-avoiding-conflicts)
  - [Step 5: Run Migrations](#step-5-run-migrations)
- [Mode Selection: API vs Web](#mode-selection-api-vs-web)
  - [Global Mode (Artisan Command)](#global-mode-artisan-command)
  - [Per-Request Mode Override](#per-request-mode-override)
- [API Documentation](#api-documentation)
  - [POST /api/v1/payment/pay](#post-apiv1paymentpay)
  - [GET /api/v1/payment/test-pay](#get-apiv1paymenttest-pay)
  - [GET /api/v1/payment/validate](#get-apiv1paymentvalidate)
  - [Postman Examples](#postman-examples)
- [Web Documentation](#web-documentation)
  - [GET /checkout](#get-checkout)
  - [POST /checkout](#post-checkout)
  - [GET /payment/validate](#get-paymentvalidate)
- [Integration with Existing Projects](#integration-with-existing-projects)
  - [Basic Usage (Service Layer)](#basic-usage-service-layer)
  - [Linking to Your Models (Polymorphic)](#linking-to-your-models-polymorphic)
  - [Listening for Payment Events](#listening-for-payment-events)
- [Database Schema](#database-schema)
- [Gateway Credentials Reference](#gateway-credentials-reference)
- [File Structure](#file-structure)
- [Troubleshooting](#troubleshooting)

---

## Supported Gateways

| Gateway     | Key           | Status     | Auth Type          |
|-------------|---------------|------------|--------------------|
| SSLCommerz  | `sslcommerz`  | ✅ Ready   | Store ID / Password |
| ShurjoPay   | `shurjopay`   | ✅ Ready   | Username / Password |
| AamarPay    | `aamarpay`    | ✅ Ready   | Store ID / Signature Key |
| bKash       | `bikash`      | ✅ Ready   | App Key / Secret + RSA |
| Nagad       | `nagad`       | ✅ Ready   | Merchant ID + RSA Keys |
| Stripe      | `stripe`      | 🔲 Placeholder | API Key / Secret |
| PayPal      | `paypal`      | 🔲 Placeholder | Client ID / Secret |

---

## Architecture Overview

```
Modules/Payment/
├── App/
│   ├── Application/
│   │   ├── Actions/          # Reusable action classes
│   │   └── Services/         # Gateway services + PaymentService
│   ├── Console/              # Artisan commands (payment:mode)
│   ├── Domain/
│   │   ├── Contracts/        # Interfaces & Factory
│   │   ├── DTO/              # Data Transfer Objects
│   │   ├── Exceptions/       # Custom exceptions
│   │   ├── Mappers/          # Response mappers
│   │   └── Models/           # Eloquent models
│   ├── Events/               # PaymentSuccessful event
│   ├── Http/
│   │   ├── Controllers/      # PaymentController
│   │   ├── Requests/         # Form requests
│   │   └── Resources/        # API resources
│   ├── Infrastructure/
│   │   ├── Events/           # Infrastructure events
│   │   └── Repositories/     # Eloquent repositories
│   ├── Providers/            # PaymentServiceProvider
│   └── Traits/               # HandlesPaymentProcess
├── config/
│   └── payment.php           # All configuration
├── database/
│   └── migrations/           # Table definitions
├── resources/
│   └── views/                # Blade templates (checkout page)
└── routes/
    ├── api.php               # API routes (middleware: api)
    └── web.php               # Web routes (middleware: web)
```

---

## Installation & Setup

### Step 1: Copy the Module

Copy the entire `Modules/Payment` directory into your target Laravel project:

```bash
cp -r Modules/Payment /path/to/your-project/Modules/Payment
```

### Step 2: Register the Module

Add the service provider to your host project's `bootstrap/providers.php` (Laravel 11+):

```php
return [
    // ... other providers
    Modules\Payment\App\Providers\PaymentServiceProvider::class,
];
```

Or for Laravel 10 and below, add it to `config/app.php`:

```php
'providers' => [
    // ... other providers
    Modules\Payment\App\Providers\PaymentServiceProvider::class,
],
```

Make sure your `composer.json` has the PSR-4 autoload entry:

```json
"autoload": {
    "psr-4": {
        "Modules\\Payment\\": "Modules/Payment/"
    }
}
```

Then run:

```bash
composer dump-autoload
```

### Step 3: Configure Environment Variables

Add the following to your `.env` file. Only fill in the gateways you need:

```env
# ─── Global Settings ───────────────────────────────────
PAYMENT_MODE=api                          # "api" or "web"
PAYMENT_DEFAULT_GATEWAY=sslcommerz        # Default gateway when none specified
PAYMENT_TABLE_PREFIX=payment_             # Prefix for all payment tables

# ─── SSLCommerz ────────────────────────────────────────
PAYMENT_SSL_COMMERZ_STORE_ID=your_store_id
PAYMENT_SSL_COMMERZ_STORE_PASSWORD=your_store_password
PAYMENT_SSL_COMMERZ_SANDBOX=true

# ─── ShurjoPay ─────────────────────────────────────────
PAYMENT_SHURJOPAY_USERNAME=sp_sandbox
PAYMENT_SHURJOPAY_PASSWORD=pyyk97hu&6u6
PAYMENT_SHURJOPAY_PREFIX=sp
PAYMENT_SHURJOPAY_SANDBOX=true

# ─── AamarPay ──────────────────────────────────────────
PAYMENT_AAMARPAY_STORE_ID=aamarpaytest
PAYMENT_AAMARPAY_SIGNATURE_KEY=your_signature_key
PAYMENT_AAMARPAY_SANDBOX=true

# ─── bKash ─────────────────────────────────────────────
PAYMENT_BIKASH_APP_KEY=your_app_key
PAYMENT_BIKASH_APP_SECRET=your_app_secret
PAYMENT_BIKASH_USERNAME=your_username
PAYMENT_BIKASH_PASSWORD=your_password
PAYMENT_BIKASH_SANDBOX=true

# ─── Nagad ─────────────────────────────────────────────
PAYMENT_NAGAD_MERCHANT_ID=your_merchant_id
PAYMENT_NAGAD_MERCHANT_NUMBER=01XXXXXXXXX
PAYMENT_NAGAD_PUBLIC_KEY=your_rsa_public_key
PAYMENT_NAGAD_PRIVATE_KEY=your_rsa_private_key
PAYMENT_NAGAD_SANDBOX=true
```

### Step 4: Table Prefix (Avoiding Conflicts)

The module supports **dynamic table prefixing** so your payment tables never conflict with your host application's tables. Set this in your `.env`:

```env
PAYMENT_TABLE_PREFIX=payment_
```

This creates the following tables:

| Prefix Value     | Customers Table        | Invoices Table        | Transactions Table        |
|------------------|------------------------|-----------------------|---------------------------|
| *(empty)*        | `customers`            | `invoices`            | `transactions`            |
| `payment_`       | `payment_customers`    | `payment_invoices`    | `payment_transactions`    |
| `pm_`            | `pm_customers`         | `pm_invoices`         | `pm_transactions`         |
| `pay_`           | `pay_customers`        | `pay_invoices`        | `pay_transactions`        |

> **Important:** Set the prefix BEFORE running migrations. If you change the prefix later, the old tables remain and new tables are created.

### Step 5: Run Migrations

```bash
php artisan migrate
```

The module auto-discovers its own migrations from `Modules/Payment/database/migrations/`.

---

## Mode Selection: API vs Web

The module supports two modes of operation:

| Mode  | Callback URLs               | Response Type      | Use Case                        |
|-------|-----------------------------|--------------------|---------------------------------|
| `api` | `/api/v1/payment/validate`  | JSON responses     | Mobile apps, React, Vue, Flutter |
| `web` | `/payment/validate`         | Blade redirects    | Traditional Laravel Blade apps   |

### Global Mode (Artisan Command)

Switch the module's mode globally using the built-in Artisan command:

```bash
# Enable API mode (for mobile/SPA frontends)
php artisan payment:mode api

# Enable Web mode (for Blade/server-rendered apps)
php artisan payment:mode web
```

This updates `PAYMENT_MODE` in your `.env` file automatically. After switching, clear config cache:

```bash
php artisan config:clear
```

### Per-Request Mode Override

You can also override the global mode per-request in your code:

```php
// Force web mode for this specific payment, regardless of global setting
$paymentService
    ->gateway('shurjopay')
    ->mode('web')          // Override here
    ->amount(500)
    ->customer($user)
    ->pay();
```

---

## API Documentation

All API routes use the `api` middleware group and return JSON responses.

### POST `/api/v1/payment/pay`

Initiate a new payment.

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
    "amount": 250,
    "gateway": "shurjopay"
}
```

**Success Response (200):**
```json
{
    "status": "success",
    "data": {
        "tran_id": "txn_6a12e4b8340b7",
        "sp_order_id": "sp6a12e4b88a1f0",
        "amount": "250.00"
    },
    "redirectUrl": "https://sandbox.securepay.shurjopayment.com/spaycheckout/?token=..."
}
```

**Error Response (400):**
```json
{
    "status": "error",
    "message": "ShurjoPay Authentication failed: ..."
}
```

### GET `/api/v1/payment/test-pay`

Quick browser test route. Opens the gateway checkout page directly.

**Query Parameters:**

| Param     | Default      | Description              |
|-----------|-------------|--------------------------|
| `amount`  | `10`        | Payment amount in BDT    |
| `gateway` | `sslcommerz`| Gateway key to test      |

**Example:**
```
http://127.0.0.1:8000/api/v1/payment/test-pay?amount=100&gateway=shurjopay
```

### GET `/api/v1/payment/validate`

Called automatically by payment gateways after checkout. Validates the transaction and updates the database.

**Query Parameters (auto-appended by gateways):**

| Param         | Source       | Description                    |
|---------------|-------------|--------------------------------|
| `gateway`     | All         | Which gateway sent the callback |
| `val_id`      | SSLCommerz  | Validation ID                  |
| `order_id`    | ShurjoPay   | SP Order ID                    |
| `mer_txnid`   | AamarPay    | Merchant Transaction ID        |
| `paymentID`   | bKash       | bKash Payment ID               |
| `payment_ref_id` | Nagad    | Nagad Payment Reference        |
| `status`      | All         | `failed` / `cancelled`         |

**Success Response:**
```json
{
    "message": "Payment processed",
    "data": {
        "status": "completed",
        "data": {
            "transaction_id": "txn_6a12e4b8340b7",
            "val_id": "sp6a12e4b88a1f0"
        },
        "raw": [ ... ]
    }
}
```

### Postman Examples

**ShurjoPay:**
```json
POST http://127.0.0.1:8000/api/v1/payment/pay
{
    "amount": 250,
    "gateway": "shurjopay"
}
```

**SSLCommerz:**
```json
POST http://127.0.0.1:8000/api/v1/payment/pay
{
    "amount": 500,
    "gateway": "sslcommerz"
}
```

**AamarPay:**
```json
POST http://127.0.0.1:8000/api/v1/payment/pay
{
    "amount": 100,
    "gateway": "aamarpay"
}
```

**bKash:**
```json
POST http://127.0.0.1:8000/api/v1/payment/pay
{
    "amount": 300,
    "gateway": "bikash"
}
```

**Nagad:**
```json
POST http://127.0.0.1:8000/api/v1/payment/pay
{
    "amount": 150,
    "gateway": "nagad"
}
```

---

## Web Documentation

All web routes use the `web` middleware group and return Blade views or redirects.

### GET `/checkout`

Renders the checkout form (Blade view).

```
http://127.0.0.1:8000/checkout
```

### POST `/checkout`

Processes the checkout form. Validates input, creates a payment, and redirects the user to the gateway's hosted checkout page.

**Form Fields:**

| Field     | Required | Validation         |
|-----------|----------|--------------------|
| `amount`  | Yes      | numeric, min:10    |
| `gateway` | Yes      | in:sslcommerz,bikash,aamarpay,shurjopay |

**On success:** Redirects to gateway checkout page.
**On error:** Redirects back to `/checkout` with error messages.

### GET `/payment/validate`

Web callback URL. Same validation logic as the API version, but used when `PAYMENT_MODE=web`. Gateways redirect the user here after payment.

---

## Integration with Existing Projects

### Basic Usage (Service Layer)

In **any** controller of your host application:

```php
use Modules\Payment\App\Application\Services\PaymentService;

class OrderController extends Controller
{
    public function checkout(Request $request, PaymentService $paymentService)
    {
        $response = $paymentService
            ->gateway('shurjopay')         // Pick your gateway
            ->amount(2500.00)              // Amount in BDT
            ->currency('BDT')             // Optional (default: BDT)
            ->customer($request->user())   // Auth user or array
            ->pay();

        // For API: return the redirect URL as JSON
        return response()->json([
            'redirect_url' => $response->redirectUrl,
            'transaction_id' => $response->data['tran_id'],
        ]);
    }
}
```

### Linking to Your Models (Polymorphic)

The `invoiceable_type` and `invoiceable_id` columns on the `invoices` table allow you to link a payment to **any model** in your host application (Flight Booking, Course Purchase, Order, etc.).

```php
// Your host app controller
$order = Order::find(42);

$response = $paymentService
    ->gateway('sslcommerz')
    ->amount($order->total)
    ->payable($order)              // Links this invoice to Order #42
    ->customer($request->user())
    ->pay();
```

After payment, the `invoices` table will contain:
```
invoiceable_type = "App\Models\Order"
invoiceable_id   = 42
```

You can then access the linked model:
```php
$invoice = PaymentInvoice::find(1);
$order = $invoice->invoiceable;   // Returns the Order model
```

### Listening for Payment Events

When a payment succeeds, the module fires a `PaymentSuccessful` event. Your host application can listen for this to trigger business logic **without modifying the Payment module**.

**Step 1: Create a Listener in your host app:**

```php
// app/Listeners/HandlePaymentSuccess.php
namespace App\Listeners;

use Modules\Payment\App\Events\PaymentSuccessful;

class HandlePaymentSuccess
{
    public function handle(PaymentSuccessful $event)
    {
        $invoice = $event->invoice;
        $payableModel = $event->payableModel;  // e.g., your Order, Booking, etc.

        if ($payableModel instanceof \App\Models\Order) {
            $payableModel->update(['status' => 'confirmed']);
        }

        if ($payableModel instanceof \App\Models\CourseEnrollment) {
            $payableModel->update(['is_active' => true]);
        }
    }
}
```

**Step 2: Register the Listener in `EventServiceProvider`:**

```php
protected $listen = [
    \Modules\Payment\App\Events\PaymentSuccessful::class => [
        \App\Listeners\HandlePaymentSuccess::class,
    ],
];
```

---

## Database Schema

The module creates 3 tables (with your configured prefix):

### `{prefix}customers`

| Column        | Type        | Description                         |
|---------------|-------------|-------------------------------------|
| `id`          | BIGINT PK   | Auto-increment                     |
| `name`        | VARCHAR     | Customer name                       |
| `email`       | VARCHAR     | Customer email (nullable)           |
| `phone`       | VARCHAR     | Customer phone (nullable)           |
| `host_user_id`| VARCHAR     | ID from your host app's users table |
| `created_at`  | TIMESTAMP   |                                     |
| `updated_at`  | TIMESTAMP   |                                     |

### `{prefix}invoices`

| Column            | Type        | Description                              |
|-------------------|-------------|------------------------------------------|
| `id`              | BIGINT PK   | Auto-increment                          |
| `customer_id`     | BIGINT FK   | References `{prefix}customers.id`       |
| `invoiceable_type`| VARCHAR     | Polymorphic model class (nullable)       |
| `invoiceable_id`  | BIGINT      | Polymorphic model ID (nullable)          |
| `invoice_number`  | VARCHAR     | Unique invoice number (e.g., `INV-xxx`) |
| `total_amount`    | DECIMAL     | Total payment amount                     |
| `status`          | ENUM        | `paid`, `unpaid`, `cancelled`, `failed` |
| `created_at`      | TIMESTAMP   |                                          |
| `updated_at`      | TIMESTAMP   |                                          |

### `{prefix}transactions`

| Column            | Type        | Description                                 |
|-------------------|-------------|---------------------------------------------|
| `id`              | BIGINT PK   | Auto-increment                             |
| `invoice_id`      | BIGINT FK   | References `{prefix}invoices.id`           |
| `transaction_id`  | VARCHAR     | Unique gateway transaction ID              |
| `gateway`         | VARCHAR     | Gateway class name (e.g., `ShurjoPayService`)|
| `amount`          | DECIMAL     | Transaction amount                          |
| `currency`        | VARCHAR(10) | Currency code (default: `BDT`)             |
| `status`          | ENUM        | `pending`, `paid`, `failed`, `cancelled`, `ipn` |
| `gateway_response`| JSON        | Full raw response from gateway (nullable)  |
| `created_at`      | TIMESTAMP   |                                             |
| `updated_at`      | TIMESTAMP   |                                             |

---

## Gateway Credentials Reference

### SSLCommerz
- **Portal:** [https://developer.sslcommerz.com](https://developer.sslcommerz.com)
- **Sandbox Credentials:** Available on the developer portal after registration
- **Auth:** `store_id` + `store_password`

### ShurjoPay
- **Portal:** [https://shurjopay.com.bd](https://shurjopay.com.bd)
- **Sandbox Credentials:** `sp_sandbox` / `pyyk97hu&6u6`
- **Auth:** `username` + `password`

### AamarPay
- **Portal:** [https://aamarpay.com](https://aamarpay.com)
- **Sandbox Credentials:** `aamarpaytest` / (signature key from portal)
- **Auth:** `store_id` + `signature_key`

### bKash (Tokenized)
- **Portal:** [https://developer.bka.sh](https://developer.bka.sh)
- **Auth:** `app_key` + `app_secret` + `username` + `password`
- **Note:** Uses RSA token-based authentication

### Nagad
- **Portal:** [https://nagad.com.bd](https://nagad.com.bd)
- **Auth:** `merchant_id` + `merchant_number` + RSA `public_key` + RSA `private_key`
- **Note:** Requires real RSA key pairs; no simple sandbox credentials

---

## File Structure

```
Modules/Payment/
│
├── App/
│   ├── Application/
│   │   ├── Actions/
│   │   │   ├── CapturePaymentAction.php
│   │   │   ├── CreatePaymentAction.php
│   │   │   ├── ListTransactionsAction.php
│   │   │   ├── RefundPaymentAction.php
│   │   │   └── VerifyWebhookAction.php
│   │   └── Services/
│   │       ├── AbstractGatewayService.php     # Base class for all gateways
│   │       ├── AamarPayService.php
│   │       ├── BikashService.php
│   │       ├── NagadService.php
│   │       ├── PaymentService.php             # ⭐ Main orchestrator
│   │       ├── PayPalService.php
│   │       ├── ShurjoPayService.php
│   │       ├── SslCommerzService.php
│   │       └── StripeService.php
│   │
│   ├── Console/
│   │   └── PaymentModeCommand.php             # php artisan payment:mode
│   │
│   ├── Domain/
│   │   ├── Contracts/
│   │   │   ├── PaymentGatewayFactory.php      # Gateway resolver
│   │   │   ├── PaymentGatewayInterface.php
│   │   │   └── TransactionRepository.php
│   │   ├── DTO/
│   │   │   ├── PaymentRequestDto.php
│   │   │   ├── PaymentResponseDto.php
│   │   │   └── RefundDto.php
│   │   ├── Exceptions/
│   │   │   ├── InvalidGatewayException.php
│   │   │   └── PaymentFailedException.php
│   │   ├── Mappers/
│   │   │   └── GatewayResponseMapper.php
│   │   └── Models/
│   │       ├── PaymentCustomer.php
│   │       ├── PaymentInvoice.php
│   │       └── PaymentTransaction.php
│   │
│   ├── Events/
│   │   └── PaymentSuccessful.php              # Fired on successful payment
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PaymentController.php
│   │   ├── Requests/
│   │   │   ├── CreatePaymentRequest.php
│   │   │   ├── RefundPaymentRequest.php
│   │   │   └── VerifyWebhookRequest.php
│   │   └── Resources/
│   │       ├── PaymentResource.php
│   │       └── TransactionResource.php
│   │
│   ├── Infrastructure/
│   │   ├── Events/
│   │   │   ├── PaymentCreated.php
│   │   │   └── PaymentRefunded.php
│   │   └── Repositories/
│   │       └── EloquentTransactionRepository.php
│   │
│   ├── Providers/
│   │   └── PaymentServiceProvider.php         # Module bootstrap
│   │
│   └── Traits/
│       ├── HandlesGatewayExceptions.php
│       └── HandlesPaymentProcess.php
│
├── config/
│   └── payment.php                            # All gateway config
│
├── database/
│   ├── factories/
│   │   └── PaymentTransactionFactory.php
│   ├── migrations/
│   │   └── 2026_05_23_000001_create_payment_tables.php
│   └── seeders/
│       └── PaymentTransactionSeeder.php
│
├── resources/
│   └── views/
│       └── checkout.blade.php                 # Web checkout form
│
├── routes/
│   ├── api.php                                # API routes (middleware: api)
│   └── web.php                                # Web routes (middleware: web)
│
└── README.md                                  # This file
```

---

## Troubleshooting

### "Invalid Nagad public key"
Nagad requires real RSA key pairs. Make sure you set `PAYMENT_NAGAD_PUBLIC_KEY` and `PAYMENT_NAGAD_PRIVATE_KEY` in your `.env`.

### "Cannot assign null to property of type string"
Your `.env` is missing a required gateway credential. The module safely handles this now with `?? ''` fallbacks, but you should still fill in the credentials for the gateways you intend to use.

### "Please check your order id" (ShurjoPay)
ShurjoPay verification uses the `sp_order_id` (appended by the gateway to the return URL), not your internal `txn_` ID. The module handles this automatically.

### SSLCommerz returns massive JSON
The module strips the `desc` array (containing all bank/card logos) from the response. You only get the clean `tran_id`, `amount`, and `sessionkey`.

### Gateway always defaults to SSLCommerz
Make sure you are sending the `gateway` field in your POST body as **JSON** (not form-data) with `Content-Type: application/json` header in Postman.

### Tables already exist after changing prefix
Changing `PAYMENT_TABLE_PREFIX` creates new tables with the new prefix. You must manually drop the old tables if they are no longer needed.

---

## Quick Start Checklist

- [ ] Copy `Modules/Payment` to your project
- [ ] Register `PaymentServiceProvider` in `bootstrap/providers.php`
- [ ] Add PSR-4 autoload entry to `composer.json`
- [ ] Run `composer dump-autoload`
- [ ] Set `PAYMENT_TABLE_PREFIX` in `.env`
- [ ] Set `PAYMENT_MODE` in `.env` (`api` or `web`)
- [ ] Add gateway credentials to `.env`
- [ ] Run `php artisan migrate`
- [ ] Test: `php artisan payment:mode api`
- [ ] Test: Open `http://127.0.0.1:8000/api/v1/payment/test-pay?gateway=shurjopay&amount=100`

---

**Built with ❤️ for reusable, modular Laravel architecture.**
