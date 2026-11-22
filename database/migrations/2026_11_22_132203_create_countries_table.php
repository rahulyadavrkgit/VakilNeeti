<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso2', 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // India is the country whose States/UTs, districts and cities are stored in the system.
        DB::table('countries')->insert([
            'name' => 'India',
            'iso2' => 'IN',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
