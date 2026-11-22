<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contacts: a person can belong to a company (another client of type company/organisation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('client_type')->constrained('clients')->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('alternate_mobile');
            $table->string('website')->nullable()->after('email');
            $table->string('locality')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['phone', 'website', 'locality']);
        });
    }
};
