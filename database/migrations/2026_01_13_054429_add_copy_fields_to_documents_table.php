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
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('parent_document_id')->nullable()->after('id')->constrained('documents')->nullOnDelete();
            $table->boolean('is_copy')->default(false)->after('parent_document_id');
            $table->integer('copy_number')->nullable()->after('is_copy');

            $table->index('parent_document_id');
            $table->index('is_copy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['parent_document_id']);
            $table->dropIndex(['parent_document_id']);
            $table->dropIndex(['is_copy']);
            $table->dropColumn(['parent_document_id', 'is_copy', 'copy_number']);
        });
    }
};
