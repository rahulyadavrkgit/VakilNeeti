<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedInteger('admin_access_version')->default(0);
        });

        Schema::create('admin_audit_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->index();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->unsignedBigInteger('case_id')->nullable()->index();
            $table->string('action', 80);
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('admin_review_feedback', function (Blueprint $table): void {
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->string('finding', 30);
            $table->string('status', 24);
            $table->timestamp('updated_at');
            $table->primary(['case_id', 'finding']);
            $table->index(['finding', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_review_feedback');
        Schema::dropIfExists('admin_audit_entries');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['suspended_at', 'admin_access_version']);
        });
    }
};
