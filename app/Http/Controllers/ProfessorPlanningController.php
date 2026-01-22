<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfessorPlanningController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->role === 'student') {
            abort(403, 'Students cannot access the professor supervision page.');
        }
        $data = $request->validate([
            'exam_session_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'professor_id' => ['nullable', 'integer'],
        ]);

        $examSessions = DB::table('exam_sessions')
            ->orderByDesc('id')
            ->get(['id', 'name', 'starts_on', 'ends_on']);

        // Role-based department filtering
        if ($user->isDepartmentHead()) {
            $departments = DB::table('departments')
                ->where('id', $user->department_id)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            $data['department_id'] = $user->department_id;
        } elseif ($user->role === 'professor') {
            // Professors see only their department
            $departments = DB::table('departments')
                ->where('id', $user->department_id)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            $data['department_id'] = $user->department_id;

            // Auto-filter to show only their own assignments
            // You'll need to link the user to a professor record
            // For now, we'll just filter by department
        } else {
            // Admins and Deans see all
            $departments = DB::table('departments')
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        }

        $professors = DB::table('professors')
            ->when(
                $user->role === 'professor' && $user->department_id,
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'department_id']);

        $assignments = collect();

        if (!empty($data['exam_session_id'])) {
            $assignments = DB::table('scheduled_exam_professors as sep')
                ->join('professors as p', 'p.id', '=', 'sep.professor_id')
                ->join('departments as dep', 'dep.id', '=', 'p.department_id')
                ->join('scheduled_exams as se', 'se.id', '=', 'sep.scheduled_exam_id')
                ->join('time_slots as ts', 'ts.id', '=', 'se.time_slot_id')
                ->join('modules as m', 'm.id', '=', 'se.module_id')
                ->join('groups as g', 'g.id', '=', 'se.group_id')
                ->join('specialties as sp', 'sp.id', '=', 'g.specialty_id')
                ->where('se.exam_session_id', $data['exam_session_id'])
                ->when($data['department_id'] ?? null, fn($q, $v) => $q->where('p.department_id', $v))
                ->when($data['professor_id'] ?? null, fn($q, $v) => $q->where('p.id', $v))
                ->select([
                    'p.id as professor_id',
                    DB::raw("CONCAT(p.last_name, ' ', p.first_name) as professor_name"),
                    'dep.name as department_name',
                    'ts.exam_date',
                    'ts.slot_index',
                    'ts.starts_at',
                    'ts.ends_at',
                    'm.code as module_code',
                    'm.name as module_name',
                    'g.name as group_name',
                    'sp.name as specialty_name',
                    'se.id as scheduled_exam_id',
                    'se.student_count',
                ])
                ->orderBy('professor_name')
                ->orderBy('ts.exam_date')
                ->orderBy('ts.slot_index')
                ->get();

            $examIds = $assignments->pluck('scheduled_exam_id')->unique()->toArray();

            if (!empty($examIds)) {
                $roomsByExam = DB::table('scheduled_exam_rooms as ser')
                    ->join('rooms as r', 'r.id', '=', 'ser.room_id')
                    ->whereIn('ser.scheduled_exam_id', $examIds)
                    ->select('ser.scheduled_exam_id', 'r.name')
                    ->orderBy('r.name')
                    ->get()
                    ->groupBy('scheduled_exam_id');

                $assignments = $assignments->map(function ($assign) use ($roomsByExam) {
                    $rooms = $roomsByExam[$assign->scheduled_exam_id] ?? collect();
                    $roomCount = $rooms->count();

                    if ($roomCount === 0) {
                        $assign->rooms = '-';
                    } elseif ($roomCount === 1) {
                        $assign->rooms = $rooms->first()->name;
                    } else {
                        $assign->rooms = "Multiple rooms ({$roomCount})";
                    }

                    return $assign;
                });
            }

            $page = request()->get('page', 1);
            $perPage = 50;
            $total = $assignments->count();
            $assignments = new \Illuminate\Pagination\LengthAwarePaginator(
                $assignments->forPage($page, $perPage),
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return view('planning.professors', compact('assignments', 'examSessions', 'departments', 'professors'));
    }
}
