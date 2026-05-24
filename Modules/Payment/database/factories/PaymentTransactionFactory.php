<?php
namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payment\App\Domain\Models\PaymentTransaction;
use Illuminate\Support\Str;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'invoice_id' => $this->faker->numberBetween(1, 100),
            'transaction_id' => Str::uuid()->toString(),
            'gateway' => $this->faker->randomElement(['sslcommerz', 'bikash', 'nagad', 'paypal']),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'gateway_response' => null,
        ];
    }
}
