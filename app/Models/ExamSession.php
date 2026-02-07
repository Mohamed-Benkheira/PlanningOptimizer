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
        'dept_approved_at' => 'datetime',
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

    public function deptApprover()
    {
        return $this->belongsTo(User::class, 'dept_approved_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Department Head Approval Status
    public function isDeptPending(): bool
    {
        return $this->dept_approval_status === 'pending';
    }

    public function isDeptApproved(): bool
    {
        return $this->dept_approval_status === 'approved';
    }

    public function isDeptRejected(): bool
    {
        return $this->dept_approval_status === 'rejected';
    }

    // Dean Approval Status
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

    // Check if fully approved by both
    public function isFullyApproved(): bool
    {
        return $this->isDeptApproved() && $this->isApproved();
    }

    // Check if ready for dean approval
    public function isReadyForDeanApproval(): bool
    {
        return $this->isDeptApproved() && $this->isPending();
    }

    // Scope for visible schedules (only fully approved)
    public function scopeVisibleToStudentsAndProfessors($query)
    {
        return $query->where('dept_approval_status', 'approved')
            ->where('approval_status', 'approved');
    }
}
