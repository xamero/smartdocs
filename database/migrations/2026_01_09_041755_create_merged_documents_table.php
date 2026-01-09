<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merged_documents', function (Blueprint $table) {
            $table->id();
            $table->string('master_tracking_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_type')->default('pdf');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('current_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->json('embedded_qr_metadata')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->date('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('master_tracking_number');
            $table->index('current_office_id');
        });

        Schema::create('merged_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merged_document_id')->constrained('merged_documents')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->integer('sequence')->default(1);
            $table->timestamps();

            $table->unique(['merged_document_id', 'document_id']);
            $table->index('merged_document_id');
            $table->index('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merged_document_items');
        Schema::dropIfExists('merged_documents');
    }
};
