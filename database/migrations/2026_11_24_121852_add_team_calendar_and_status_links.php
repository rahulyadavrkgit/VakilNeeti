<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Teams: a junior or clerk works in the workspace of the advocate who invited them (team_owner_id),
 *   with a role; invites wait by mobile number until that person signs in.
 * - Calendar sync: a private link per person for Google Calendar / iPhone (calendar_token).
 * - Client status link: a private read-only page per case to send to the client (status_token).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_owner_id')->nullable()->after('preferences')->constrained('users')->nullOnDelete();
            $table->string('team_role', 20)->nullable()->after('team_owner_id');
            $table->string('calendar_token', 64)->nullable()->unique()->after('team_role');
        });

        Schema::create('team_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('mobile', 20);
            $table->string('role', 20);
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'mobile']);
            $table->index('mobile');
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->string('status_token', 64)->nullable()->unique()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropUnique(['status_token']);
            $table->dropColumn('status_token');
        });

        Schema::dropIfExists('team_invites');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_owner_id');
            $table->dropUnique(['calendar_token']);
            $table->dropColumn(['team_role', 'calendar_token']);
        });
    }
};
