<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
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
