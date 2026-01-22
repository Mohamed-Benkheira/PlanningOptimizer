<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use DepartmentScoped;
    use HasFactory;

    protected $guarded = [];
    // No relations needed yet (until Exams table)
}
