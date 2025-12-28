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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('student')->index();
            $table->foreignId('department_id')->nullable()
                ->constrained()->nullOnDelete()->index();

            // Optional links (nice for “my schedule” pages later)
            $table->foreignId('professor_id')->nullable()
                ->constrained()->nullOnDelete()->unique();
            $table->foreignId('student_id')->nullable()
                ->constrained()->nullOnDelete()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('professor_id');
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn('role');
        });
    }

};
