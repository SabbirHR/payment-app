<?php
namespace Modules\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payment\App\Domain\Models\PaymentTransaction;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        PaymentTransaction::factory()->count(20)->create();
    }
}
