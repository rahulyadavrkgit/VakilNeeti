<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'mobile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mobile', 20)->nullable()->unique();
            });
        }

        if (!Schema::hasColumn('users', 'mobile_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('mobile_verified_at')->nullable();
            });
        }

        if (!Schema::hasTable('user_registration_otps')) {
            Schema::create('user_registration_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()
                    ->constrained('users')->cascadeOnDelete();
                $table->string('email')->nullable()->index();
                $table->string('mobile', 20)->nullable()->index();
                $table->string('email_otp_hash')->nullable();
                $table->string('mobile_otp_hash')->nullable();
                $table->timestamp('expires_at');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_registration_otps');

        if (Schema::hasColumn('users', 'mobile_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mobile_verified_at');
            });
        }

        if (Schema::hasColumn('users', 'mobile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['mobile']);
                $table->dropColumn('mobile');
            });
        }
    }
};