<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ScheduledExamRoom extends Model
{
    use DepartmentScoped;
    protected $table = 'scheduled_exam_rooms';

    protected $fillable = [
        'scheduled_exam_id',
        'room_id',
        'seats_allocated',
    ];

    protected function casts(): array
    {
        return [
            'seats_allocated' => 'integer',
        ];
    }

    public function scheduledExam(): BelongsTo
    {
        return $this->belongsTo(ScheduledExam::class, 'scheduled_exam_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
