<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPlanningController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'professor') {
            abort(403, 'Sorry you can\'t access this page   .');
        }
        $data = $request->validate([
            'exam_session_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'specialty_id' => ['nullable', 'integer'],
            'level_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
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
        } elseif ($user->role === 'student') {
            // Students see all departments (they can browse)
            $departments = DB::table('departments')
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        } else {
            // Admins and Deans see all
            $departments = DB::table('departments')
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        }

        $specialties = DB::table('specialties')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        $levels = DB::table('levels')
            ->orderBy('year_number')
            ->get(['id', 'code', 'year_number']);

        $groups = DB::table('groups')
            ->join('specialties as sp', 'sp.id', '=', 'groups.specialty_id')
            ->orderBy('groups.name')
            ->get(['groups.id', 'groups.name', 'groups.specialty_id', 'groups.level_id', 'sp.department_id']);

        $exams = collect();

        if (!empty($data['exam_session_id'])) {
            $exams = DB::table('scheduled_exams as se')
                ->join('time_slots as ts', 'ts.id', '=', 'se.time_slot_id')
                ->join('modules as m', 'm.id', '=', 'se.module_id')
                ->join('groups as g', 'g.id', '=', 'se.group_id')
                ->join('specialties as sp', 'sp.id', '=', 'g.specialty_id')
                ->join('levels as lv', 'lv.id', '=', 'g.level_id')
                ->join('departments as dep', 'dep.id', '=', 'sp.department_id')
                ->leftJoin('scheduled_exam_rooms as ser', 'ser.scheduled_exam_id', '=', 'se.id')
                ->leftJoin('rooms as r', 'r.id', '=', 'ser.room_id')
                ->where('se.exam_session_id', $data['exam_session_id'])
                ->when($data['department_id'] ?? null, fn($q, $v) => $q->where('sp.department_id', $v))
                ->when($data['specialty_id'] ?? null, fn($q, $v) => $q->where('g.specialty_id', $v))
                ->when($data['level_id'] ?? null, fn($q, $v) => $q->where('g.level_id', $v))
                ->when($data['group_id'] ?? null, fn($q, $v) => $q->where('g.id', $v))
                ->select([
                    'ts.exam_date',
                    'ts.slot_index',
                    'ts.starts_at',
                    'ts.ends_at',
                    'm.code as module_code',
                    'm.name as module_name',
                    'g.name as group_name',
                    'sp.name as specialty_name',
                    'lv.code as level_code',
                    'dep.name as department_name',
                    DB::raw("GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') as rooms"),
                    'se.student_count',
                ])
                ->groupBy([
                    'ts.exam_date',
                    'ts.slot_index',
                    'ts.starts_at',
                    'ts.ends_at',
                    'm.code',
                    'm.name',
                    'g.name',
                    'sp.name',
                    'lv.code',
                    'dep.name',
                    'se.student_count'
                ])
                ->orderBy('ts.exam_date')
                ->orderBy('ts.slot_index')
                ->orderBy('dep.name')
                ->orderBy('sp.name')
                ->paginate(50);
        }

        return view('planning.groups', compact('exams', 'examSessions', 'departments', 'specialties', 'levels', 'groups'));
    }
}
