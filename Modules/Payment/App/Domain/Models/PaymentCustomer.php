<?php
namespace Modules\Payment\App\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentCustomer extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        $prefix = config('payment.table_prefix', '');
        return $prefix . 'customers';
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class, 'customer_id');
    }
}
