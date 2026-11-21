<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_documents', function (Blueprint $table) {
            $table->id();

            // External data provider
            $table->string('provider_key');

            $table->foreign('provider_key')
                ->references('provider_key')
                ->on('court_data_sources')
                ->restrictOnDelete();

            // Court identification
            $table->foreignId('court_id')
                ->nullable()
                ->constrained('courts')
                ->nullOnDelete();

            // Stable document identification
            $table->string('document_key', 64);

            // Cause-list information
            $table->date('listing_date')->nullable();
            $table->string('court_scope')->nullable();
            $table->string('category')->nullable();
            $table->string('list_type')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('document_label')->nullable();

            // Original source and downloaded files
            $table->text('source_url');
            $table->string('pdf_file_path')->nullable();
            $table->string('text_file_path')->nullable();

            // File information
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();

            // Processing status
            $table->string('download_status')->default('pending');
            $table->string('extraction_status')->default('pending');

            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('extracted_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider_key', 'document_key'],
                'court_documents_provider_key_unique'
            );

            $table->index(['court_id', 'listing_date']);
            $table->index(['listing_date', 'list_type']);
            $table->index(['download_status', 'extraction_status']);
        });

        Schema::create('cause_list_appearances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('court_document_id')
                ->constrained('court_documents')
                ->cascadeOnDelete();

            // Link to an existing VakilNeeti case when reliably matched.
            $table->foreignId('legal_case_id')
                ->nullable()
                ->constrained('legal_cases')
                ->nullOnDelete();

            // Stable identity within the source document
            $table->string('record_key', 64);

            // Listing information
            $table->string('item_number')->nullable();
            $table->string('case_reference')->nullable();
            $table->string('case_type')->nullable();
            $table->string('classification')->nullable();
            $table->string('court_room')->nullable();

            // Extracted case and party information
            $table->text('case_title_raw')->nullable();
            $table->text('petitioner_raw')->nullable();
            $table->text('respondent_raw')->nullable();
            $table->json('advocates_raw')->nullable();
            $table->text('judge_name')->nullable();

            // Applications and connected matters
            $table->json('ia_details')->nullable();
            $table->json('connected_matters')->nullable();
            $table->json('listing_notes')->nullable();

            // Original extracted text and location
            $table->longText('raw_text')->nullable();
            $table->unsignedInteger('source_line')->nullable();

            // Matching and verification
            $table->string('match_status')->default('unmatched');
            $table->timestamp('matched_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['court_document_id', 'record_key'],
                'cause_list_document_record_unique'
            );

            $table->index(['legal_case_id', 'court_document_id']);
            $table->index('case_reference');
            $table->index(['match_status', 'item_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cause_list_appearances');
        Schema::dropIfExists('court_documents');
    }
};
