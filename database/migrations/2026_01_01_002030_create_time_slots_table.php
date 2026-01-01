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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_session_id')
                ->constrained('exam_sessions')
                ->cascadeOnDelete();

            $table->date('exam_date');

            // 1..4 (09:40, 11:20, 13:00, 14:40)
            $table->unsignedSmallInteger('slot_index');

            $table->time('starts_at');
            $table->time('ends_at')->nullable();

            $table->timestamps();

            $table->unique(['exam_session_id', 'exam_date', 'slot_index'], 'time_slots_session_date_slot_unique');
            $table->index(['exam_session_id', 'exam_date'], 'time_slots_session_date_index');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
