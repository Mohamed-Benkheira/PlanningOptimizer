<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Professor Planning - Exam Supervision</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            position: relative;
            color: #333;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef3 25%, #f9f9f9 50%, #e3e9f0 75%, #f5f7fa 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating shapes for subtle depth */
        .bg-shape {
            position: fixed;
            border-radius: 50%;
            opacity: 0.03;
            z-index: -1;
            animation: float 25s ease-in-out infinite;
        }

        .bg-shape:nth-child(1) {
            width: 400px;
            height: 400px;
            background: #111;
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        .bg-shape:nth-child(2) {
            width: 500px;
            height: 500px;
            background: #333;
            bottom: -250px;
            right: -250px;
            animation-delay: 5s;
        }

        .bg-shape:nth-child(3) {
            width: 350px;
            height: 350px;
            background: #555;
            top: 50%;
            right: -175px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -30px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        h2 {
            margin-bottom: 8px;
            color: #111;
        }

        .card {
            border: 1px solid rgba(221, 221, 221, 0.5);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .card:hover {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .row { display: flex; gap: 12px; flex-wrap: wrap; }
        .field { display: flex; flex-direction: column; min-width: 220px; }
        label { font-size: 13px; margin-bottom: 6px; color: #333; font-weight: 500; }
        select { padding: 8px; border: 1px solid #ccc; border-radius: 6px; background: white; }
        select:focus { outline: none; border-color: #111; }
        
        button {
            padding: 10px 14px;
            border: 1px solid #111;
            background: #111;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        button:hover { background: #333; transform: translateY(-1px); }
        button.secondary { background: #fff; color: #111; }
        button.secondary:hover { background: #f5f5f5; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            background: rgba(255, 255, 255, 0.5);
        }
        th, td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; font-size: 14px; }
        th { background: rgba(250, 250, 250, 0.8); font-weight: 600; }
        tbody tr:hover { background: rgba(249, 249, 249, 0.8); }
        
        .muted { color: #777; font-size: 13px; }
        .errors {
            background: #fff3f3;
            border: 1px solid #ffcccc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: #f1f1f1;
            font-size: 12px;
            margin-left: 4px;
        }
        .actions { display: flex; gap: 8px; align-items: end; }
        .time-cell { white-space: nowrap; font-family: monospace; font-size: 13px; }
        .professor-name { font-weight: 600; color: #111; }
        .rooms-cell { font-size: 12px; color: #555; }

        /* Responsive */
        @media (max-width: 768px) {
            body { margin: 16px; }
            .bg-shape { display: none; }
        }
    </style>
</head>
<body>
    <!-- Floating background shapes -->
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>

<h2> Professor Exam Supervision</h2>
<p class="muted">View exam supervision assignments for professors.</p>

@if ($errors->any())
    <div class="errors">
        <strong>Validation errors:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <form method="GET" action="{{ route('planning.professors') }}">
        <div class="row">
            <div class="field">
                <label>Exam session *</label>
                <select name="exam_session_id" required>
                    <option value="">-- Choose --</option>
                    @foreach ($examSessions ?? [] as $s)
                        <option value="{{ $s->id }}" @selected(request('exam_session_id') == $s->id)>
                            {{ $s->name }} ({{ $s->starts_on }} → {{ $s->ends_on }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label>Department</label>
                <select name="department_id">
                    <option value="">All</option>
                    @foreach ($departments ?? [] as $dep)
                        <option value="{{ $dep->id }}" @selected(request('department_id') == $dep->id)>
                            {{ $dep->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label>Professor</label>
                <select name="professor_id">
                    <option value="">All</option>
                    @foreach ($professors ?? [] as $prof)
                        <option value="{{ $prof->id }}" @selected(request('professor_id') == $prof->id)>
                            {{ $prof->last_name }} {{ $prof->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="actions">
                <button type="submit">Search</button>
                <a href="{{ route('planning.professors') }}">
                    <button type="button" class="secondary">Reset</button>
                </a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="row" style="justify-content: space-between; align-items: center;">
        <div>
            <strong>Results</strong>
            @if(request('exam_session_id'))
                <span class="pill">Session: {{ request('exam_session_id') }}</span>
            @endif
            @if(request('department_id'))
                <span class="pill">Dept: {{ request('department_id') }}</span>
            @endif
            @if(request('professor_id'))
                <span class="pill">Prof: {{ request('professor_id') }}</span>
            @endif
        </div>
        <div class="muted">
            @if(isset($assignments) && $assignments instanceof \Illuminate\Pagination\LengthAwarePaginator)
                Total: {{ $assignments->total() }} assignments
            @endif
        </div>
    </div>

    @if(!request('exam_session_id'))
        <p class="muted" style="margin-top: 12px;">⚠️ Choose an exam session then click Search.</p>
    @else
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Professor</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Module</th>
                        <th>Group</th>
                        <th>Specialty</th>
                        <th>Rooms</th>
                        <th>Students</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments ?? [] as $assign)
                        <tr>
                            <td class="professor-name">{{ $assign->professor_name }}</td>
                            <td>{{ $assign->department_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($assign->exam_date)->format('D, M d, Y') }}</td>
                            <td class="time-cell">{{ substr($assign->starts_at, 0, 5) }} - {{ substr($assign->ends_at, 0, 5) }}</td>
                            <td>{{ $assign->module_name }}</td>
                            <td>{{ $assign->group_name }}</td>
                            <td>{{ $assign->specialty_name }}</td>
                            <td class="rooms-cell">{{ $assign->rooms ?? '-' }}</td>
                            <td style="text-align: center;">{{ $assign->student_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: #999;">
                                📭 No professor assignments for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($assignments) && $assignments instanceof \Illuminate\Pagination\LengthAwarePaginator && $assignments->hasPages())
            <div style="margin-top: 16px;">
                {{ $assignments->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.querySelector('select[name="department_id"]');
    const professorSelect = document.querySelector('select[name="professor_id"]');

    const allProfessors = @json($professors ?? []);
    const selectedDept = "{{ request('department_id') }}";
    const selectedProf = "{{ request('professor_id') }}";

    function filterProfessors() {
        const deptId = departmentSelect.value;
        const currentProf = professorSelect.value;
        
        professorSelect.innerHTML = '<option value="">All</option>';
        
        allProfessors
            .filter(p => !deptId || p.department_id == deptId)
            .forEach(p => {
                const opt = new Option(`${p.last_name} ${p.first_name}`, p.id);
                opt.selected = (p.id == currentProf) || (p.id == selectedProf && !currentProf);
                professorSelect.add(opt);
            });
    }

    if (departmentSelect) departmentSelect.addEventListener('change', filterProfessors);

    filterProfessors();
});
</script>

</body>
</html>
