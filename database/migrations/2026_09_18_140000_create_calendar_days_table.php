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
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date')->unique()->index();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->index();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('day_of_week'); // 1 = Senin, 7 = Minggu
            $table->string('day_name', 20); // Senin, Selasa, dst.
            $table->boolean('is_working_day')->default(true)->index(); // true = hari kerja, false = hari libur
            $table->string('type', 40)->default('workday')->index(); // workday, weekend, national_holiday, collective_leave, custom_holiday, custom_workday
            $table->string('name')->nullable(); // Keterangan nama hari libur / catatan khusus
            $table->string('source', 30)->default('default'); // default, api_sync, manual
            $table->boolean('is_overridden')->default(false)->index(); // Ditandai jika diubah manual dari default/API

            // Audit UUID pengguna yang melakukan update / create
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
