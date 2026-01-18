<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // Add as nullable first
            $table->foreignId('department_id')
                ->nullable()
                ->after('code')
                ->constrained('departments')
                ->onDelete('restrict');
        });

        // Populate from specialties (automatic data migration)
        DB::statement("
            UPDATE modules m 
            SET department_id = s.department_id 
            FROM specialties s 
            WHERE m.specialty_id = s.id
        ");

        // Make NOT NULL after population
        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
