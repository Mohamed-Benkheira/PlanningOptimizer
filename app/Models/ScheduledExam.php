<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ScheduledExam extends Model
{
    use DepartmentScoped;
    protected $table = 'scheduled_exams';

    protected $fillable = [
        'exam_session_id',
        'module_id',
        'group_id',
        'time_slot_id',
        'student_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'student_count' => 'integer',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ScheduledExamRoom::class, 'scheduled_exam_id');
    }

}
