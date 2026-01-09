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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_type'); // e.g., 'incoming', 'outgoing', 'internal'
            $table->string('source')->nullable(); // Source office or external entity
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('confidentiality', ['public', 'confidential', 'restricted'])->default('public');
            $table->enum('status', ['draft', 'registered', 'in_transit', 'received', 'in_action', 'completed', 'archived', 'returned'])->default('draft');
            $table->foreignId('current_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('receiving_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_received')->nullable();
            $table->date('date_due')->nullable();
            $table->boolean('is_merged')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->date('archived_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tracking_number');
            $table->index('document_type');
            $table->index('status');
            $table->index('current_office_id');
            $table->index('priority');
            $table->index('date_received');

            // Fulltext index only for MySQL/MariaDB
            $driver = Schema::getConnection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                $table->fullText(['title', 'description']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
