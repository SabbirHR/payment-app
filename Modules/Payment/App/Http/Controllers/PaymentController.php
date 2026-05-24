<?php
namespace Modules\Payment\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\App\Application\Services\PaymentService;

class PaymentController extends Controller
{
    public function testPay(Request $request, PaymentService $paymentService)
    {
        $amount = $request->query('amount', 10);
        $gateway = $request->query('gateway', 'sslcommerz');

        try {
            // This is a test route to instantly redirect to the payment gateway
            $response = $paymentService
                ->gateway($gateway)
                ->amount((float) $amount)
                ->customer(['name' => 'Test User', 'email' => 'test@test.com', 'phone' => '01700000000'])
                ->pay();

            if (!empty($response->redirectUrl)) {
                return redirect($response->redirectUrl);
            }

            return response()->json($response->data);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function pay(Request $request, PaymentService $paymentService)
    {
        $gateway = $request->input('gateway', config('payment.default'));

        try {
            // Example integration
            $response = $paymentService
                ->gateway($gateway)
                ->amount($request->input('amount', 100))
                ->invoice($request->input('invoice_id', 1))
                ->customer($request->user())
                ->pay();

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function validateT(Request $request, PaymentService $paymentService)
    {
        // Fix malformed URLs caused by gateways appending ? instead of &
        $gatewayParam = $request->input('gateway', '');
        $rawQuery = $request->server('QUERY_STRING');
        if (!empty($rawQuery) && strpos($rawQuery, '?') !== false) {
            // Replace all '?' with '&' in the query string to allow proper parsing
            $fixed = str_replace('?', '&', $rawQuery);
            parse_str($fixed, $queryVars);
            $request->replace($queryVars);
        }

        $status = strtolower($request->input('status') ?? '');
        $payStatus = strtolower($request->input('pay_status') ?? '');

        // In case the status value itself contains a stray '?', split it out
        if (strpos($status, '?') !== false) {
            [$status, $extra] = explode('?', $status, 2);
            parse_str($extra, $extraVars);
            $request->merge($extraVars);
        }
        if (strpos($payStatus, '?') !== false) {
            [$payStatus, $extra] = explode('?', $payStatus, 2);
            parse_str($extra, $extraVars);
            $request->merge($extraVars);
        }

        // Extract the transaction identifier from all possible gateway params
        $valId = $request->input('val_id')
            ?? $request->input('paymentID')
            ?? $request->input('payment_ref_id')
            ?? $request->input('mer_txnid')
            ?? $request->input('pg_txnid')
            ?? $request->input('tran_id')
            ?? $request->input('order_id');
        $gateway = $request->input('gateway', config('payment.default'));

        // Handle failed callbacks — update transaction in DB
        if ($status === 'failed' || $payStatus === 'failed') {
            if ($valId) {
                $paymentService->gateway($gateway)->updateTransactionStatus($valId, 'failed', $request->all());
            }
            return response()->json(['message' => 'Payment Failed', 'status' => 'failed'], 400);
        }

        // Handle cancelled callbacks — update transaction in DB
        if ($status === 'cancel' || $status === 'cancelled' || $payStatus === 'cancelled') {
            if ($valId) {
                $paymentService->gateway($gateway)->updateTransactionStatus($valId, 'cancelled', $request->all());
            }
            return response()->json(['message' => 'Payment Cancelled by User', 'status' => 'cancelled'], 400);
        }

        if (!$valId) {
            return response()->json(['message' => 'Validation ID missing'], 400);
        }

        // Idempotency: check if this transaction was already paid
        $existingTransaction = \Modules\Payment\App\Domain\Models\PaymentTransaction::where('transaction_id', $valId)
            ->where('status', 'paid')
            ->first();

        if ($existingTransaction) {
            return response()->json([
                'message' => 'Payment already processed',
                'data' => [
                    'status' => 'paid',
                    'transaction_id' => $valId,
                ]
            ]);
        }

        // Dispatch job or validate synchronously
        $result = $paymentService
            ->gateway($gateway)
            ->validateTransaction($valId);

        return response()->json(['message' => 'Payment processed', 'data' => $result]);
    }

    public function processCheckout(Request $request, PaymentService $paymentService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'gateway' => 'required|in:sslcommerz,bikash,aamarpay,shurjopay',
        ]);

        $amount = $request->input('amount');
        $gateway = $request->input('gateway');

        try {
            $response = $paymentService
                ->gateway($gateway)
                ->amount((float) $amount)
                ->customer([
                    'name' => 'Demo Customer',
                    'email' => 'demo@example.com',
                    'phone' => '01770618575'
                ])
                ->pay();

            if (!empty($response->redirectUrl)) {
                return redirect($response->redirectUrl);
            }

            return response()->json($response->data);
        } catch (\Exception $e) {
            return redirect('/checkout')->withErrors(['gateway' => $e->getMessage()])->withInput();
        }
    }
}
