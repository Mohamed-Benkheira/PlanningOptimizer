<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use DepartmentScoped;
    protected $guarded = [];
    // No direct relation needed yet unless you want $semester->examSessions()
}
