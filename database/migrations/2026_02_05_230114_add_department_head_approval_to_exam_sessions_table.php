<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->enum('dept_approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('approval_status');
            $table->foreignId('dept_approved_by')
                ->nullable()
                ->after('approved_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('dept_approved_at')
                ->nullable()
                ->after('approved_at');
            $table->text('dept_rejection_reason')
                ->nullable()
                ->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropForeign(['dept_approved_by']);
            $table->dropColumn([
                'dept_approval_status',
                'dept_approved_by',
                'dept_approved_at',
                'dept_rejection_reason'
            ]);
        });
    }
};
