<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links between an advocate's cases: "arises_from" (an appeal or revision in a higher court
     * from the linked lower-court case) or "connected" (tagged or related matters).
     */
    public function up(): void
    {
        Schema::create('case_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('linked_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->string('relation', 20);
            $table->timestamps();

            $table->unique(['case_id', 'linked_case_id']);
            $table->index('linked_case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_links');
    }
};
