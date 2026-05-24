<?php
namespace Modules\Payment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Add required webhook payload validation rules
        ];
    }
}
