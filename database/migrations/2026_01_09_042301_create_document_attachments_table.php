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
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash')->nullable(); // SHA-256 hash for integrity verification
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('upload_ip_address', 45)->nullable();
            $table->text('upload_user_agent')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('version')->default(1); // Version number for revisions
            $table->enum('status', ['active', 'replaced', 'deleted'])->default('active');
            $table->foreignId('replaced_by_id')->nullable()->constrained('document_attachments')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index('document_id');
            $table->index('uploaded_by');
            $table->index('file_hash');
            $table->index('status');
            $table->index('replaced_by_id');
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
    }
};
