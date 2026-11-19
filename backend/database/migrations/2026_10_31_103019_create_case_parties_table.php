<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_parties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->string('external_party_id')->nullable();
            $table->string('party_name');
            $table->string('party_type')->nullable();
            $table->unsignedInteger('party_number')->nullable();

            $table->boolean('is_client')->default(false);
            $table->string('relationship_type')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_parties');
    }
};