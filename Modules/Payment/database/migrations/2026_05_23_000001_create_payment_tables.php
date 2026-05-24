<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $prefix = config('payment.table_prefix', '');

        // 1. Create Customers Table (Independent of host app's users table)
        Schema::create($prefix . 'customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('host_user_id')->nullable()->comment('Optional ID from the host application');
            $table->timestamps();
        });

        // 2. Create Invoices Table
        Schema::create($prefix . 'invoices', function (Blueprint $table) use ($prefix) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('invoiceable_type')->nullable();
            $table->unsignedBigInteger('invoiceable_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['paid', 'unpaid', 'cancelled', 'failed'])->default('unpaid');
            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('id')
                  ->on($prefix . 'customers')
                  ->onDelete('set null');
        });

        // 3. Create Transactions Table
        Schema::create($prefix . 'transactions', function (Blueprint $table) use ($prefix) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('transaction_id')->unique();
            $table->string('gateway');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('BDT');
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'ipn'])->default('pending');
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')
                  ->references('id')
                  ->on($prefix . 'invoices')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $prefix = config('payment.table_prefix', '');

        Schema::dropIfExists($prefix . 'transactions');
        Schema::dropIfExists($prefix . 'invoices');
        Schema::dropIfExists($prefix . 'customers');
    }
};
