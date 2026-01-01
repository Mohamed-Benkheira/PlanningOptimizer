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
        Schema::create('scheduled_exam_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('scheduled_exam_id')
                ->constrained('scheduled_exams')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->restrictOnDelete();

            $table->unsignedInteger('seats_allocated');

            $table->timestamps();

            $table->unique(['scheduled_exam_id', 'room_id'], 'scheduled_exam_rooms_unique');
            $table->index(['room_id'], 'scheduled_exam_rooms_room_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_exam_rooms');
    }
};
