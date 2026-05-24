<?php
namespace Modules\Payment\App\Domain\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PaymentTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'gateway_response' => 'array',
    ];

    public function getTable()
    {
        $prefix = config('payment.table_prefix', '');
        return $prefix . 'transactions';
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'invoice_id');
    }

    public function updateStatusFromPending(string $status, ?array $gatewayResponse = null)
    {
        if ($this->status === 'pending') {
            $data = ['status' => $status];
            if ($gatewayResponse !== null) {
                $data['gateway_response'] = $gatewayResponse;
            }
            $this->update($data);
        }
    }
}
