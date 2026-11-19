<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('police_stations', function (Blueprint $table) {
            // Make police_district_id nullable so specialist/non-territorial units are not forced into territorial districts
            $table->foreignId('police_district_id')->nullable()->change();

            if (!Schema::hasColumn('police_stations', 'police_sub_division_id')) {
                $table->foreignId('police_sub_division_id')->nullable()->after('police_district_id')->constrained('police_sub_divisions')->nullOnDelete();
            }
            if (!Schema::hasColumn('police_stations', 'is_specialist_unit')) {
                $table->boolean('is_specialist_unit')->default(false)->after('station_type');
            }
            if (!Schema::hasColumn('police_stations', 'specialist_unit_category')) {
                $table->string('specialist_unit_category')->nullable()->after('is_specialist_unit');
            }
            if (!Schema::hasColumn('police_stations', 'sho_name')) {
                $table->string('sho_name')->nullable()->after('email');
            }
            if (!Schema::hasColumn('police_stations', 'sho_designation')) {
                $table->string('sho_designation')->nullable()->after('sho_name');
            }
            if (!Schema::hasColumn('police_stations', 'jurisdiction_area')) {
                $table->text('jurisdiction_area')->nullable()->after('sho_designation');
            }
            if (!Schema::hasColumn('police_stations', 'citizen_portal_url')) {
                $table->text('citizen_portal_url')->nullable()->after('official_url');
            }
            if (!Schema::hasColumn('police_stations', 'control_room_phone')) {
                $table->string('control_room_phone', 100)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('police_stations', function (Blueprint $table) {
            $table->dropForeign(['police_sub_division_id']);
            $table->dropColumn([
                'police_sub_division_id',
                'is_specialist_unit',
                'specialist_unit_category',
                'sho_name',
                'sho_designation',
                'jurisdiction_area',
                'citizen_portal_url',
                'control_room_phone',
            ]);
        });
    }
};