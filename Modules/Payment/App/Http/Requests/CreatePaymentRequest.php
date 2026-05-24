<?php
namespace Modules\Payment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'invoice_id' => 'required|integer',
            'gateway' => 'nullable|string',
        ];
    }
}
