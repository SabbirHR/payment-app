<?php
namespace Modules\Payment\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
