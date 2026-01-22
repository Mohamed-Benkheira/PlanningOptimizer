<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use DepartmentScoped;
    protected $guarded = [];
    public function groups()
    {
        return $this->hasMany(Group::class);
    }
    public function examSessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}
