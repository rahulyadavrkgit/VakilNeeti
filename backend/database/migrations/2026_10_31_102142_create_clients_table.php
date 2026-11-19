<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_code')->unique()->nullable();
            $table->enum('client_type', ['individual', 'company', 'organisation'])->default('individual');

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name')->nullable();

            $table->string('mobile', 20)->nullable();
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('pin_code', 10)->nullable();

            $table->string('occupation')->nullable();
            $table->string('organisation_name')->nullable();
            $table->string('referred_by')->nullable();

            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};