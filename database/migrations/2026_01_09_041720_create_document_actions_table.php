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
        Schema::create('document_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices');
            $table->foreignId('action_by')->constrained('users');
            $table->enum('action_type', ['approve', 'note', 'comply', 'sign', 'return', 'forward'])->default('note');
            $table->text('remarks')->nullable();
            $table->string('memo_file_path')->nullable();
            $table->boolean('is_office_head_approval')->default(false);
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index('document_id');
            $table->index('office_id');
            $table->index('action_type');
            $table->index('action_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_actions');
    }
};
