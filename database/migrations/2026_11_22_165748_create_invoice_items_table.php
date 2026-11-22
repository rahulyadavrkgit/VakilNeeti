<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('rate', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_mode', 10)->default('exclusive');
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('source_type', 20)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
