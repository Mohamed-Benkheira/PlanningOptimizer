<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
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
