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
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();

            $table->decimal('note', 5, 2)->nullable(); // optional grade/mark
            $table->string('status', 20)->default('enrolled'); // enrolled, exempted...

            $table->timestamps();

            $table->unique(['student_id', 'module_id', 'exam_session_id'], 'inscriptions_unique');
            $table->index(['exam_session_id', 'module_id']);
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
