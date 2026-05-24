<?php
namespace Modules\Payment\App\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\App\Traits\HandlesPaymentProcess;

class PaymentInvoice extends Model
{
    use HandlesPaymentProcess;

    protected $guarded = ['id'];

    public function getTable()
    {
        $prefix = config('payment.table_prefix', '');
        return $prefix . 'invoices';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(PaymentCustomer::class, 'customer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'invoice_id');
    }

    public function invoiceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsPaid()
    {
        $this->update(['status' => 'paid']);
        
        // As defined in the blueprint, we pass the invoiceable (e.g. Booking) to the trait
        if ($this->invoiceable) {
            $this->handlePaymentProcess($this->invoiceable);
        }
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }
}
