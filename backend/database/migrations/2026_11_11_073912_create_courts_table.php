
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();

            // Basic court identification
            $table->string('court_name');
            $table->string('court_code')->nullable()->unique();

            // Court classification
            $table->string('court_type');
            $table->string('court_level')->nullable();

            // Geographical details
            $table->string('country')->default('India');
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('pincode', 10)->nullable();

            // Official information
            $table->string('official_website')->nullable();
            $table->string('official_email')->nullable();
            $table->string('official_phone')->nullable();

            // Additional information
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['court_type', 'state']);
            $table->index(['district', 'city']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
