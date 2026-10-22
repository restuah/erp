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
        Schema::create('customer_banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('bank_name', 100);
            $table->string('bank_account_number', 50)->index();
            $table->string('bank_account_name', 150);
            $table->string('bank_branch', 100)->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_banks');
    }
};
