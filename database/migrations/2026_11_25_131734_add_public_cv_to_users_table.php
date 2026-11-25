<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('public_cv')->nullable();
            $table->string('public_cv_username', 30)->nullable()->unique();
            $table->timestamp('public_cv_published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_cv_username']);
            $table->dropColumn(['public_cv_username', 'public_cv_published_at', 'public_cv']);
        });
    }
};
