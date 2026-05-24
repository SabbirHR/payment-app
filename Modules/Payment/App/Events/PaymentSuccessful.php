<?php
namespace Modules\Payment\App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\App\Domain\Models\PaymentInvoice;

class PaymentSuccessful
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;
    public $payableModel;

    public function __construct(PaymentInvoice $invoice)
    {
        $this->invoice = $invoice;
        $this->payableModel = $invoice->invoiceable;
    }
}
