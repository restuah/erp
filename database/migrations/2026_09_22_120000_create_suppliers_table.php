<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('supplier_type', ['direct', 'indirect'])->default('direct');
            $table->string('supplier_code', 50)->unique();
            $table->string('supplier_name', 255);
            $table->enum('status', ['active', 'nonactive'])->default('active');
            $table->foreignUuid('term_of_payment_id')->nullable()->constrained('term_of_payments')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('zipcode', 20)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->string('office_phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('marketing_name', 100)->nullable();
            $table->string('marketing_phone', 50)->nullable();
            $table->string('marketing_email', 100)->nullable();
            $table->string('accounting_name', 100)->nullable();
            $table->string('accounting_phone', 50)->nullable();
            $table->string('accounting_email', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('default_currency', 10)->nullable()->default('IDR');
            $table->string('country_code', 10)->nullable()->default('ID');
            $table->string('delivery_period', 100)->nullable();
            $table->string('delivery_time', 20)->nullable();
            $table->string('delivery_end_time', 20)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_code');
            $table->index('supplier_name');
            $table->index(['supplier_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
