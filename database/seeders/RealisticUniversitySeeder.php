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
use App\Models\Module;
use App\Models\Semester;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;

class RealisticUniversitySeeder extends Seeder
{
    public function run(): void
    {
        // Increase memory for bulk operations
        ini_set('memory_limit', '1024M');

        // 1. Reference Data
        $year = AcademicYear::firstOrCreate(['code' => '2025-2026'], ['is_active' => true]);
        $s1 = Semester::firstOrCreate(['code' => 'S1'], ['name' => 'Semester 1']);

        $session = ExamSession::firstOrCreate([
            'academic_year_id' => $year->id,
            'semester_id' => $s1->id,
            'name' => 'Winter Regular Session',
        ], [
            'starts_on' => '2026-01-10',
            'ends_on' => '2026-01-25',
        ]);

        // Levels Config (The Heavy Structure)
        $levelsConfig = [
            'L1' => ['cycle' => 'Bachelor', 'year' => 1, 'groups' => [5, 10]],
            'L2' => ['cycle' => 'Bachelor', 'year' => 2, 'groups' => [5, 7]],
            'L3' => ['cycle' => 'Bachelor', 'year' => 3, 'groups' => [4, 6]],
            'M1' => ['cycle' => 'Master', 'year' => 1, 'groups' => [4, 5]],
            'M2' => ['cycle' => 'Master', 'year' => 2, 'groups' => [4, 5]],
        ];

        foreach ($levelsConfig as $code => $conf) {
            Level::firstOrCreate(
                ['code' => $code],
                ['cycle' => $conf['cycle'], 'year_number' => $conf['year']]
            );
        }

        // Departments (7 Depts * 3 Specs each = 21 Specs)
        $departmentsData = [
            'Computer Science' => ['Software Engineering', 'Artificial Intelligence', 'Cyber Security'],
            'Mathematics' => ['Applied Mathematics', 'Statistics & Data', 'Pure Mathematics'],
            'Physics' => ['Theoretical Physics', 'Material Sciences', 'Energy Systems'],
            'Biology' => ['Molecular Biology', 'Ecology', 'Biotechnology'],
            'Business' => ['Marketing Strategy', 'Corporate Finance', 'Human Resources'],
            'Law' => ['International Law', 'Public Administration', 'Business Law'],
            'Languages' => ['English Literature', 'Applied Translation', 'Linguistics'],
        ];

        DB::beginTransaction();

        try {
            foreach ($departmentsData as $deptName => $specialties) {
                $this->command->info("Seeding Dept: $deptName...");

                $deptCode = strtoupper(substr(str_replace(' ', '', $deptName), 0, 3));
                $dept = Department::create(['name' => $deptName, 'code' => $deptCode]);

                // 2. Professors (120 per Dept to handle ~10 groups each max)
                $professors = Professor::factory(120)->create(['department_id' => $dept->id]);
                // Workload tracker in memory: [prof_id => current_group_count]
                $profWorkload = $professors->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

                foreach ($specialties as $specName) {
                    $spec = Specialty::create([
                        'name' => $specName,
                        'code' => strtoupper(substr(str_replace(' ', '', $specName), 0, 3)) . rand(100, 999),
                        'department_id' => $dept->id,
                    ]);

                    foreach ($levelsConfig as $lvlCode => $conf) {
                        $level = Level::where('code', $lvlCode)->first();

                        // Modules: 6-8 per formation
                        $modules = Module::factory(rand(6, 8))->create([
                            'specialty_id' => $spec->id,
                            'level_id' => $level->id,
                            'semester' => 1, // Only S1 active
                        ]);

                        // Groups: Dynamic Count
                        $groupCount = rand($conf['groups'][0], $conf['groups'][1]);

                        for ($i = 1; $i <= $groupCount; $i++) {
                            $group = Group::create([
                                'name' => "G{$i}",
                                'specialty_id' => $spec->id,
                                'level_id' => $level->id,
                                'academic_year_id' => $year->id,
                                'capacity' => rand(20, 25), // Reduced size 20-25
                            ]);

                            // Attach modules to group
                            $group->modules()->attach($modules->pluck('id'));

                            // --- SMART PROFESSOR ASSIGNMENT (Load Balanced) ---
                            $assignedProfs = [];
                            $attempts = 0;

                            // We need ~4 professors per group (one for every ~2 modules)
                            while (count($assignedProfs) < 4 && $attempts < 50) {
                                $attempts++;
                                $candidateId = array_rand($profWorkload);

                                // Enforce Constraint: Max 10 Groups
                                if ($profWorkload[$candidateId] < 10 && !in_array($candidateId, $assignedProfs)) {
                                    $assignedProfs[] = $candidateId;
                                    $profWorkload[$candidateId]++;
                                }
                            }

                            if (!empty($assignedProfs)) {
                                $group->professors()->attach($assignedProfs);
                            }
                            // --------------------------------------------------

                            // Create Students
                            $students = Student::factory($group->capacity)->create(['group_id' => $group->id]);

                            // Inscriptions (Bulk)
                            $inscriptionRows = [];
                            foreach ($students as $student) {
                                foreach ($modules as $module) {
                                    $inscriptionRows[] = [
                                        'student_id' => $student->id,
                                        'module_id' => $module->id,
                                        'exam_session_id' => $session->id,
                                        'status' => 'enrolled',
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }

                            foreach (array_chunk($inscriptionRows, 1000) as $chunk) {
                                Inscription::insert($chunk);
                            }
                        }
                    }
                }
            }

            // 3. Rooms (Critical for Optimization Constraints)
            // 40 rooms of size 20 (Conflict Generators!)
            Room::factory(40)->create(['type' => 'Classroom', 'capacity' => 20]);
            // 20 rooms of size 30 (Fits small groups)
            Room::factory(20)->create(['type' => 'Large Classroom', 'capacity' => 30]);
            // 10 Amphis (For merged exams)
            Room::factory(10)->create(['type' => 'Amphitheater', 'capacity' => 200]);

            DB::commit();
            $this->command->info("Seeder Finished: 7 Depts, 21 Specs, ~13k Students.");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
