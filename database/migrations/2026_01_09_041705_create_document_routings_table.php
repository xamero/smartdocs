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
        Schema::create('document_routings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('from_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('to_office_id')->constrained('offices');
            $table->foreignId('routed_by')->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'in_transit', 'received', 'returned'])->default('pending');
            $table->timestamp('routed_at');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->integer('sequence')->default(1);
            $table->timestamps();

            $table->index('document_id');
            $table->index('to_office_id');
            $table->index('status');
            $table->index(['document_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_routings');
    }
};
