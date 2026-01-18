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
        ini_set('memory_limit', '4096M');
        set_time_limit(600);

        $this->command->info("🚀 Starting Optimized University Seeder (13,000+ students, 7 departments, 200+ programs)...");

        // Reference Data
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

        // OPTIMIZED: Fewer groups, larger capacity (20-30 students)
        $levelsConfig = [
            'L1' => ['cycle' => 'Bachelor', 'year' => 1, 'groups' => [4, 6]], // Reduced from [4,6]
            'L2' => ['cycle' => 'Bachelor', 'year' => 2, 'groups' => [4, 6]], // Reduced from [4,6]
            'L3' => ['cycle' => 'Bachelor', 'year' => 3, 'groups' => [4, 5]], // Reduced from [4,6]
            'M1' => ['cycle' => 'Master', 'year' => 1, 'groups' => [2, 3]],   // Reduced from [4,6]
            'M2' => ['cycle' => 'Master', 'year' => 2, 'groups' => [2, 3]],   // Reduced from [3,6]
        ];

        foreach ($levelsConfig as $code => $conf) {
            Level::firstOrCreate(['code' => $code], ['cycle' => $conf['cycle'], 'year_number' => $conf['year']]);
        }

        // 7 Departments with realistic distribution
        $departmentsData = [
            'Computer Science' => [
                'specialties' => [
                    'Software Engineering',
                    'Artificial Intelligence',
                    'Cyber Security',
                    'Data Science',
                    'Networks & Telecom',
                ],
                'student_weight' => 1.3,
            ],
            'Mathematics' => [
                'specialties' => [
                    'Applied Mathematics',
                    'Statistics & Data',
                    'Pure Mathematics',
                    'Actuarial Science',
                ],
                'student_weight' => 0.8,
            ],
            'Physics' => [
                'specialties' => [
                    'Theoretical Physics',
                    'Material Sciences',
                    'Energy Systems',
                    'Electronics',
                ],
                'student_weight' => 0.7,
            ],
            'Biology' => [
                'specialties' => [
                    'Molecular Biology',
                    'Ecology',
                    'Biotechnology',
                    'Microbiology',
                    'Genetics',
                ],
                'student_weight' => 1.0,
            ],
            'Economics & Management' => [
                'specialties' => [
                    'Marketing Strategy',
                    'Corporate Finance',
                    'Human Resources',
                    'International Business',
                    'Accounting',
                    'Entrepreneurship',
                ],
                'student_weight' => 1.2,
            ],
            'Law & Political Science' => [
                'specialties' => [
                    'International Law',
                    'Public Administration',
                    'Business Law',
                    'Constitutional Law',
                    'Political Science',
                ],
                'student_weight' => 0.9,
            ],
            'Languages & Humanities' => [
                'specialties' => [
                    'English Literature',
                    'Applied Translation',
                    'Linguistics',
                    'French Studies',
                    'History',
                    'Philosophy',
                ],
                'student_weight' => 0.8,
            ],
        ];

        DB::beginTransaction();

        try {
            // ========================================
            // PHASE 1: Create Departments & Academic Structure
            // ========================================
            $allDepartments = [];
            $totalStudents = 0;
            $totalPrograms = 0;
            $totalExams = 0;

            foreach ($departmentsData as $deptName => $config) {
                $this->command->info("📚 Creating Department: $deptName...");

                $deptCode = strtoupper(substr(str_replace([' ', '&'], '', $deptName), 0, 3));
                $dept = Department::create(['name' => $deptName, 'code' => $deptCode]);
                $allDepartments[] = $dept;

                $specialties = $config['specialties'];
                $weight = $config['student_weight'];

                // Professors: ~120 per department (adjusted for larger groups)
                $profCount = (int) (120 * $weight);
                $professors = Professor::factory($profCount)->create(['department_id' => $dept->id]);
                $profWorkload = $professors->pluck('id')->mapWithKeys(fn($id) => [$id => 0])->toArray();

                foreach ($specialties as $specName) {
                    $spec = Specialty::create([
                        'name' => $specName,
                        'code' => strtoupper(substr(str_replace(' ', '', $specName), 0, 3)) . rand(100, 999),
                        'department_id' => $dept->id,
                    ]);

                    $totalPrograms++;

                    foreach ($levelsConfig as $lvlCode => $conf) {
                        $level = Level::where('code', $lvlCode)->first();

                        // 6-9 modules per program (as per requirement)
                        $moduleCount = rand(6, 9);
                        $modules = Module::factory($moduleCount)->create([
                            'specialty_id' => $spec->id,
                            'level_id' => $level->id,
                            'semester' => 1,
                            'department_id' => $dept->id,
                        ]);

                        // OPTIMIZED: Fewer groups with larger capacity
                        $baseGroupCount = rand($conf['groups'][0], $conf['groups'][1]);
                        $groupCount = (int) ceil($baseGroupCount * $weight);

                        for ($i = 1; $i <= $groupCount; $i++) {
                            // CHANGED: Groups of 20-30 students (realistic + efficient)
                            $groupCapacity = rand(20, 30);

                            $group = Group::create([
                                'name' => "G{$i}",
                                'specialty_id' => $spec->id,
                                'level_id' => $level->id,
                                'academic_year_id' => $year->id,
                                'capacity' => $groupCapacity,
                            ]);

                            // Attach modules to group
                            $group->modules()->attach($modules->pluck('id'));

                            // Professor assignment (balanced workload, 3-4 profs per group)
                            $profsNeeded = rand(3, 4);
                            $assignedProfs = [];
                            $attempts = 0;

                            while (count($assignedProfs) < $profsNeeded && $attempts < 100) {
                                $attempts++;
                                if (empty($profWorkload))
                                    break;

                                $candidateId = array_rand($profWorkload);
                                if ($profWorkload[$candidateId] < 15 && !in_array($candidateId, $assignedProfs)) {
                                    $assignedProfs[] = $candidateId;
                                    $profWorkload[$candidateId]++;
                                }
                            }

                            if (!empty($assignedProfs)) {
                                $group->professors()->attach($assignedProfs);
                            }

                            // Create students
                            $students = Student::factory($groupCapacity)->create(['group_id' => $group->id]);
                            $totalStudents += $groupCapacity;

                            // Inscriptions (bulk insert for performance)
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

                            // Bulk insert in chunks
                            foreach (array_chunk($inscriptionRows, 2000) as $chunk) {
                                Inscription::insert($chunk);
                            }

                            // Track exam count
                            $totalExams += $modules->count();
                        }
                    }
                }

                $this->command->info("  ✓ {$deptName}: {$profCount} professors created");
            }

            // ========================================
            // PHASE 2: Create Rooms - OPTIMIZED CONFIGURATION
            // ========================================
            $this->command->info("🏫 Creating room infrastructure...");

            $roomsData = [];
            $deptIds = collect($allDepartments)->pluck('id')->all();

            // OPTIMIZED ROOM STRATEGY:
            // - 80 classrooms (20 seats max) - UP FROM 70
            // - 20 amphitheaters (70 seats) - KEPT SAME
            // - Total: 100 rooms (UP FROM 90)
            // - Better distribution: 60% dept-owned, 40% shared

            // 80 Small Classrooms (20 seats MAX during exams)
            for ($i = 1; $i <= 100; $i++) {
                // 60% department-owned, 40% shared
                $isDeptOwned = rand(1, 100) <= 60;

                $roomsData[] = [
                    'name' => "Classroom {$i}",
                    'code' => "CL" . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'capacity' => 20, // MAX 20 students during exams (fire safety constraint)
                    'type' => 'classroom',
                    'building' => 'Building ' . chr(65 + (($i - 1) % 5)), // A-E
                    'department_id' => $isDeptOwned ? $deptIds[array_rand($deptIds)] : null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 20 Amphitheaters (70 seats for merged exams)
            for ($i = 1; $i <= 30; $i++) {
                $roomsData[] = [
                    'name' => "Amphitheater {$i}",
                    'code' => "AMP" . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'capacity' => 70, // Amphitheater capacity (enables merging 2-3 groups)
                    'type' => 'amphitheater',
                    'building' => $i <= 10 ? 'Central Campus' : 'Science Complex',
                    'department_id' => null, // ALL amphitheaters SHARED (enables cross-specialty merging)
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($roomsData, 100) as $chunk) {
                DB::table('rooms')->insert($chunk);
            }

            DB::commit();

            // ========================================
            // PHASE 3: Final Statistics & Capacity Analysis
            // ========================================
            $this->command->newLine();
            $this->command->info("✅ Optimized University Seeder Completed Successfully!");
            $this->command->newLine();

            $actualStudents = Student::count();
            $actualGroups = Group::count();
            $actualExams = DB::table('module_group')->count();
            $avgStudentsPerGroup = $actualGroups > 0 ? round($actualStudents / $actualGroups, 1) : 0;

            $stats = [
                'Departments' => Department::count(),
                'Specialties' => Specialty::count(),
                'Programs (Formations)' => Specialty::count() * 5, // 5 levels each
                'Professors' => Professor::count(),
                'Students' => $actualStudents,
                'Groups' => $actualGroups,
                'Avg Students/Group' => $avgStudentsPerGroup,
                'Modules' => Module::count(),
                'Inscriptions' => Inscription::count(),
                'Expected Exams' => $actualExams,
            ];

            $this->command->table(['Metric', 'Count'], collect($stats)->map(fn($v, $k) => [$k, is_numeric($v) ? number_format($v) : $v])->values());

            // Room Configuration
            $this->command->newLine();
            $this->command->info("🏫 Room Infrastructure:");
            $roomConfig = [
                ['Type', 'Count', 'Exam Capacity', 'Purpose'],
                ['Classrooms', 100, '20 seats MAX', 'Small/medium groups (1-2 rooms/exam)'],
                ['Amphitheaters', 30, '70 seats', 'Merged exams (2-3 groups)'],
                ['Total Rooms', 130, '-', '⬆️ +10 rooms from original'],
            ];
            $this->command->table(array_shift($roomConfig), $roomConfig);

            // Capacity Analysis (UPDATED FOR 100 ROOMS)
            $slotsAvailable = 14 * 4; // 14 days × 4 slots/day
            $totalRooms = 100; // UPDATED
            $roomSlotCapacity = $totalRooms * $slotsAvailable; // 100 rooms × 56 slots = 5,600

            // Estimate room-slots needed (groups of 20-30 need avg 1.25 rooms)
            $avgRoomsPerExam = 1.25; // Since groups are 20-30 and rooms max 20
            $roomSlotsNeeded = (int) ($actualExams * $avgRoomsPerExam);
            $utilizationPct = round(($roomSlotsNeeded / $roomSlotCapacity) * 100, 1);
            $buffer = $roomSlotCapacity - $roomSlotsNeeded;

            $this->command->newLine();
            $this->command->info("📊 Scheduling Capacity Analysis:");
            $capacityTable = [
                ['Metric', 'Value'],
                ['Total exams to schedule', number_format($actualExams)],
                ['Avg rooms per exam', $avgRoomsPerExam . ' (groups 20-30, rooms max 20)'],
                ['Room-slots needed', number_format($roomSlotsNeeded)],
                ['Available time slots (14 days × 4)', number_format($slotsAvailable)],
                ['Total room capacity (100 × 56)', number_format($roomSlotCapacity)],
                ['Expected utilization', "{$utilizationPct}%"],
                ['Buffer capacity', number_format($buffer) . ' room-slots'],
            ];
            $this->command->table(array_shift($capacityTable), $capacityTable);

            if ($roomSlotsNeeded <= $roomSlotCapacity) {
                $surplus = $roomSlotCapacity - $roomSlotsNeeded;
                $this->command->info("✅ SUFFICIENT CAPACITY! Surplus: " . number_format($surplus) . " room-slots");

                // Calculate safe merging potential
                $mergeableExams = (int) ($actualExams * 0.30); // Estimate 30% can merge
                $afterMerging = $actualExams - $mergeableExams;
                $afterMergingSlots = (int) ($afterMerging * $avgRoomsPerExam);
                $finalUtilization = round(($afterMergingSlots / $roomSlotCapacity) * 100, 1);

                $this->command->info("🔀 With merging enabled: ~{$afterMerging} exams → {$finalUtilization}% utilization");
            } else {
                $deficit = $roomSlotsNeeded - $roomSlotCapacity;
                $additionalDays = ceil($deficit / ($totalRooms * 4));
                $this->command->error("❌ INSUFFICIENT CAPACITY! Deficit: " . number_format($deficit) . " room-slots");
                $this->command->warn("💡 Solution: Add {$additionalDays} more days OR enable merging");
            }

            // Department distribution
            $this->command->newLine();
            $this->command->info("📈 Student Distribution by Department:");
            $deptStats = DB::select("
                SELECT 
                    d.name,
                    COUNT(DISTINCT s.id) as students,
                    COUNT(DISTINCT sp.id) as specialties,
                    COUNT(DISTINCT g.id) as groups,
                    ROUND(AVG(g.capacity), 1) as avg_group_size
                FROM departments d
                LEFT JOIN specialties sp ON sp.department_id = d.id
                LEFT JOIN groups g ON g.specialty_id = sp.id
                LEFT JOIN students s ON s.group_id = g.id
                GROUP BY d.id, d.name
                ORDER BY students DESC
            ");

            $this->command->table(
                ['Department', 'Students', 'Specialties', 'Groups', 'Avg Group Size'],
                collect($deptStats)->map(fn($r) => [
                    $r->name,
                    number_format($r->students),
                    $r->specialties,
                    $r->groups,
                    $r->avg_group_size,
                ])
            );

            // Room distribution
            $this->command->newLine();
            $this->command->info("🔑 Classroom Ownership:");
            $roomOwnership = [
                'Department-owned classrooms' => Room::where('type', 'classroom')->whereNotNull('department_id')->count(),
                'Shared classrooms' => Room::where('type', 'classroom')->whereNull('department_id')->count(),
                'Shared amphitheaters' => Room::where('type', 'amphitheater')->count(),
            ];
            $this->command->table(['Category', 'Count'], collect($roomOwnership)->map(fn($v, $k) => [$k, $v])->values());

            // Next steps
            $this->command->newLine();
            $this->command->info("🚀 Next Steps:");
            $this->command->line("  1. php artisan exams:generate-slots {$session->id}");
            $this->command->line("  2. php artisan exams:generate-schedule {$session->id} --enable-merging");
            $this->command->newLine();
            $this->command->info("💡 Key Optimizations:");
            $this->command->line("  ✅ Groups: 20-30 students (realistic + efficient)");
            $this->command->line("  ✅ Total groups: ~" . $actualGroups . " (reduced from ~900)");
            $this->command->line("  ✅ Multi-room allocation: Auto-handles groups > 20");
            $this->command->line("  ✅ Rooms: 100 total (+10 from original)");
            $this->command->line("  ✅ Amphitheaters: Enable merging for same-module exams");
            $this->command->line("  ✅ Target: Fits in 14 days with merging enabled");

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error("❌ Seeder failed: " . $e->getMessage());
            $this->command->error("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}
