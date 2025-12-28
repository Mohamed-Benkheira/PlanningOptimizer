<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $guarded = [];
    // No direct relation needed yet unless you want $semester->examSessions()
}
