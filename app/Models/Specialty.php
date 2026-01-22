<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use DepartmentScoped;
    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function groups()
    {
        return $this->hasMany(Group::class);
    }
    public function modules()
    {
        return $this->hasMany(Module::class);
    }
}

