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
        ini_set('memory_limit', '2048M'); // Bump memory for 130k rows

        // ... [Reference Data: Year, Semester, Session, Levels code is same] ...
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

        $levelsConfig = [
            'L1' => ['cycle' => 'Bachelor', 'year' => 1, 'groups' => [5, 10]],
            'L2' => ['cycle' => 'Bachelor', 'year' => 2, 'groups' => [5, 7]],
            'L3' => ['cycle' => 'Bachelor', 'year' => 3, 'groups' => [4, 6]],
            'M1' => ['cycle' => 'Master', 'year' => 1, 'groups' => [4, 5]],
            'M2' => ['cycle' => 'Master', 'year' => 2, 'groups' => [4, 5]],
        ];

        foreach ($levelsConfig as $code => $conf) {
            Level::firstOrCreate(['code' => $code], ['cycle' => $conf['cycle'], 'year_number' => $conf['year']]);
        }

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

                $professors = Professor::factory(120)->create(['department_id' => $dept->id]);
                $profWorkload = $professors->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

                foreach ($specialties as $specName) {
                    $spec = Specialty::create([
                        'name' => $specName,
                        'code' => strtoupper(substr(str_replace(' ', '', $specName), 0, 3)) . rand(100, 999),
                        'department_id' => $dept->id,
                    ]);

                    foreach ($levelsConfig as $lvlCode => $conf) {
                        $level = Level::where('code', $lvlCode)->first();

                        // UPDATE: 8 to 9 Modules per formation (Maxing out the constraint)
                        $modules = Module::factory(rand(8, 9))->create([
                            'specialty_id' => $spec->id,
                            'level_id' => $level->id,
                            'semester' => 1,
                        ]);

                        $groupCount = rand($conf['groups'][0], $conf['groups'][1]);

                        for ($i = 1; $i <= $groupCount; $i++) {
                            $group = Group::create([
                                'name' => "G{$i}",
                                'specialty_id' => $spec->id,
                                'level_id' => $level->id,
                                'academic_year_id' => $year->id,
                                'capacity' => rand(20, 25),
                            ]);

                            $group->modules()->attach($modules->pluck('id'));

                            // Prof Assignment Logic (Same as before)
                            $assignedProfs = [];
                            $attempts = 0;
                            while (count($assignedProfs) < 4 && $attempts < 50) {
                                $attempts++;
                                $candidateId = array_rand($profWorkload);
                                if ($profWorkload[$candidateId] < 10 && !in_array($candidateId, $assignedProfs)) {
                                    $assignedProfs[] = $candidateId;
                                    $profWorkload[$candidateId]++;
                                }
                            }
                            if (!empty($assignedProfs)) {
                                $group->professors()->attach($assignedProfs);
                            }

                            // Students
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

            // Rooms (Same Config)
            Room::factory(40)->create(['type' => 'Classroom', 'capacity' => 20]);
            Room::factory(20)->create(['type' => 'Large Classroom', 'capacity' => 30]);
            Room::factory(10)->create(['type' => 'Amphitheater', 'capacity' => 200]);

            DB::commit();
            $this->command->info("Seeder Finished. Modules/Student increased to 8-9.");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
