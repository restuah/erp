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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_code', 50)->unique()->index();
            $table->string('name', 255)->index();
            $table->string('alias', 100)->nullable();
            $table->string('tax_id', 50)->nullable(); // NPWP

            // Financial & Accounting Relations
            $table->foreignUuid('term_of_payment_id')->nullable()->constrained('term_of_payments')->nullOnDelete();
            $table->foreignUuid('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignUuid('coa_receivable_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            // Contact & Office Information
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('email', 100)->nullable();

            // Contact Person (PIC)
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_person_phone', 50)->nullable();
            $table->string('contact_person_email', 100)->nullable();

            // Multi-address support
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('tax_address')->nullable();

            // Operational parameters
            $table->integer('delivery_lead_time_days')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            // Audit columns
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
