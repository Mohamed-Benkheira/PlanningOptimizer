<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable() // Nullable for existing records
                ->after('semester_id')
                ->constrained('departments')
                ->onDelete('restrict');

            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
