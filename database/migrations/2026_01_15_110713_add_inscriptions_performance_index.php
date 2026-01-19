<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Critical index for conflict detection performance (10-20x speedup)
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->index(
                ['exam_session_id', 'student_id', 'module_id'],
                'inscriptions_conflict_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropIndex('inscriptions_conflict_lookup');
        });
    }
};
