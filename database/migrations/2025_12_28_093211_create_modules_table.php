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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code', 30)->unique();

            $table->foreignId('specialty_id')->constrained()->restrictOnDelete();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('semester'); // keep as int for now (1..2 or 1..6)
            $table->unsignedSmallInteger('credits')->default(0);

            $table->timestamps();

            $table->index(['specialty_id', 'level_id']);
            $table->index('semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
