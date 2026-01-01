<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TimeSlot extends Model
{
    protected $table = 'time_slots';

    protected $fillable = [
        'exam_session_id',
        'exam_date',
        'slot_index',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'starts_at' => 'string', // keep simple for MVP
            'ends_at' => 'string',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function scheduledExams(): HasMany
    {
        return $this->hasMany(ScheduledExam::class, 'time_slot_id');
    }
}
