<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use DepartmentScoped;
    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }
}
