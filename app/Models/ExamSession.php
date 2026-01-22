<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use DepartmentScoped;
    protected $guarded = [];
    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

}
