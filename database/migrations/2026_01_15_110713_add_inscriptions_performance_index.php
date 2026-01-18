<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Critical index for conflict detection performance (10-20x speedup)
        DB::statement("
            CREATE INDEX IF NOT EXISTS inscriptions_conflict_lookup 
            ON inscriptions(exam_session_id, student_id, module_id)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS inscriptions_conflict_lookup");
    }
};
