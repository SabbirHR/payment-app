<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .checkout-card {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .gateway-selector label {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .gateway-selector label:hover {
            background-color: #f1f3f5;
        }
        .gateway-selector input[type="radio"]:checked + label {
            border-color: #0d6efd;
            background-color: #f8f9fa;
            box-shadow: 0 0 0 1px #0d6efd;
        }
        .gateway-selector input[type="radio"] {
            display: none;
        }
        .gateway-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="checkout-card">
        <h2 class="text-center mb-4">Complete Payment</h2>
        
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('payment.checkout.process') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label for="amount" class="form-label fw-bold">Amount (TK)</label>
                <input type="number" name="amount" id="amount" class="form-control form-control-lg" placeholder="Enter amount" min="10" value="100" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Select Payment Gateway</label>
                <div class="gateway-selector">
                    <input type="radio" name="gateway" id="gateway_sslcommerz" value="sslcommerz" checked>
                    <label for="gateway_sslcommerz">
                        <span class="gateway-icon">🔒</span>
                        <strong>SSLCommerz</strong>
                    </label>

                    <input type="radio" name="gateway" id="gateway_bikash" value="bikash">
                    <label for="gateway_bikash">
                        <span class="gateway-icon">💸</span>
                        <strong>bKash</strong>
                    </label>

                    <input type="radio" name="gateway" id="gateway_aamarpay" value="aamarpay">
                    <label for="gateway_aamarpay">
                        <span class="gateway-icon">💳</span>
                        <strong>AamarPay</strong>
                    </label>

                    <input type="radio" name="gateway" id="gateway_shurjopay" value="shurjopay">
                    <label for="gateway_shurjopay">
                        <span class="gateway-icon">☀️</span>
                        <strong>ShurjoPay</strong>
                    </label>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Pay Now</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
