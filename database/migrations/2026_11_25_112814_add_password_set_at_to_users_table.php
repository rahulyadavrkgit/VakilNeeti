<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the person chose their own password. Accounts made with a sign-in code carry a random password they
 * never saw; until this is set they sign in with a code (and can set a password in Settings).
 * Administrators who already sign in with a password are marked as having one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_set_at')->nullable()->after('password');
            });
        }

        DB::table('users')->where('is_admin', true)->where('admin_password_setup', false)->whereNull('password_set_at')->update(['password_set_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
