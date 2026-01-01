<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_exams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_session_id')
                ->constrained('exam_sessions')
                ->cascadeOnDelete();

            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete();

            $table->foreignId('group_id')
                ->constrained('groups')
                ->restrictOnDelete();

            $table->foreignId('time_slot_id')
                ->constrained('time_slots')
                ->cascadeOnDelete();

            $table->unsignedInteger('student_count')->default(0);

            $table->string('status', 20)->default('draft'); // draft|validated|published (MVP)

            $table->timestamps();

            $table->unique(['exam_session_id', 'module_id', 'group_id'], 'scheduled_exams_exam_unit_unique');
            $table->index(['exam_session_id', 'time_slot_id'], 'scheduled_exams_session_slot_index');
            $table->index(['group_id', 'module_id'], 'scheduled_exams_group_module_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_exams');
    }
};
