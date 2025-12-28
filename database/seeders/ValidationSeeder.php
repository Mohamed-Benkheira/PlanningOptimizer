<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Specialty;
use App\Models\Level;
use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Student;
use App\Models\Professor;
use App\Models\User;

class ValidationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Structure
        $deptInfo = Department::create(['name' => 'Informatique', 'code' => 'INFO']);
        $deptMath = Department::create(['name' => 'Mathématiques', 'code' => 'MATH']); // Created real dept

        $spec = Specialty::create(['name' => 'ISIL', 'code' => 'ISIL', 'department_id' => $deptInfo->id]);
        $level = Level::create(['code' => 'L3', 'cycle' => 'Licence', 'year_number' => 3]);
        $year = AcademicYear::create(['code' => '2025-2026', 'is_active' => true]);

        // 2. Create Relations
        $group = Group::create([
            'name' => 'G1',
            'specialty_id' => $spec->id,
            'level_id' => $level->id,
            'academic_year_id' => $year->id,
            'capacity' => 25
        ]);

        $student = Student::create([
            'matricule' => '20250001',
            'first_name' => 'Ali',
            'last_name' => 'Benali',
            'group_id' => $group->id
        ]);

        $prof = Professor::create([
            'first_name' => 'Ahmed',
            'last_name' => 'Prof',
            'department_id' => $deptInfo->id,
            'status' => 'active'
        ]);

        // 3. Create Users
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => 'super_admin'
        ]);

        User::factory()->create([
            'name' => 'Dept Head Info',
            'email' => 'head_info@test.com',
            'role' => 'department_head',
            'department_id' => $deptInfo->id
        ]);

        User::factory()->create([
            'name' => 'Dept Head Math',
            'email' => 'head_math@test.com',
            'role' => 'department_head',
            'department_id' => $deptMath->id // Valid ID now
        ]);
    }
}
