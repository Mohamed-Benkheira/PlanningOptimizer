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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('matricule', 30)->unique();
            $table->string('first_name');
            $table->string('last_name');

            $table->foreignId('group_id')->constrained()->restrictOnDelete();

            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->index('group_id');
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
