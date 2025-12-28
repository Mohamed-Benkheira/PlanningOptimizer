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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code', 30)->nullable()->unique();

            $table->unsignedInteger('capacity'); // required (capacity constraints)
            $table->string('type', 30)->nullable();     // amphi, salle, labo...
            $table->string('building', 50)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('capacity');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
