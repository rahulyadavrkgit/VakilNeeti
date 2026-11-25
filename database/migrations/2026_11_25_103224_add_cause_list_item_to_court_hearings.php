<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The case's item number and the number of cases in that court's cause list for the date, entered with the
 * date (district cause lists are behind the eCourts captcha, so they cannot be read automatically).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            if (! Schema::hasColumn('court_hearings', 'item_number')) {
                $table->string('item_number', 20)->nullable()->after('court_room');
            }

            if (! Schema::hasColumn('court_hearings', 'list_total')) {
                $table->unsignedSmallInteger('list_total')->nullable()->after('item_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            $table->dropColumn(['item_number', 'list_total']);
        });
    }
};
