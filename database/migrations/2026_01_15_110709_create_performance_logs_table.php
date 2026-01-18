<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')
                ->constrained('exam_sessions')
                ->onDelete('cascade');
            $table->integer('exam_count')->unsigned();
            $table->decimal('duration_seconds', 8, 2);
            $table->string('algorithm', 100);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('exam_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_logs');
    }
};
