<?php
namespace Modules\Payment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string'
        ];
    }
}
