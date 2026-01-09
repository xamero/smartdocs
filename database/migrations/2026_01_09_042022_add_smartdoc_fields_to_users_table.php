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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'encoder', 'approver', 'viewer'])->default('viewer')->after('email');
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete()->after('role');
            $table->boolean('is_active')->default(true)->after('office_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->index('role');
            $table->index('office_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['office_id']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'office_id', 'is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
