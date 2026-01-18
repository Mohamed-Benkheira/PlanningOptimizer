<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_exam_professors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_exam_id')
                ->constrained('scheduled_exams')
                ->onDelete('cascade');
            $table->foreignId('professor_id')
                ->constrained('professors')
                ->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['scheduled_exam_id', 'professor_id'], 'scheduled_exam_professors_unique');

            // Fast lookup by professor (for "my schedule" views)
            $table->index('professor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_exam_professors');
    }
};
