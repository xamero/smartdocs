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
        Schema::create('qr_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained('qr_codes')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('scanned_by_type')->nullable(); // 'user', 'public', 'system'
            $table->foreignId('scanned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('scan_location')->nullable();
            $table->enum('scan_type', ['physical', 'merged', 'digital'])->default('physical');
            $table->foreignId('merged_document_id')->nullable()->constrained('merged_documents')->nullOnDelete();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('qr_code_id');
            $table->index('document_id');
            $table->index('scanned_at');
            $table->index('scan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_scan_logs');
    }
};
