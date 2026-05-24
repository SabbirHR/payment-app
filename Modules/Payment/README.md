# Payment Module

A Laravel module that provides a clean, contract‑based multi‑gateway payment integration.

## Features
- Factory pattern for gateway resolution
- Domain‑driven actions (Create, Capture, Refund, List, Verify)
- Service layer wrapping gateway SDKs (Stripe, PayPal, etc.)
- Transaction repository and event broadcasting
- Ready for unit testing via PHPUnit

## Installation
```bash
composer require your‑vendor/payment-module
php artisan module:publish payment --provider=Modules\Payment\Providers\PaymentServiceProvider
```

## Usage
Inject `PaymentService` (or call the actions directly) in your controllers:
```php
$response = (new CreatePaymentAction())
    ->execute(new PaymentRequestDto(...));
```

Refer to the `config/payment.php` file for gateway credentials.
