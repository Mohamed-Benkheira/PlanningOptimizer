<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use DepartmentScoped;
    protected $guarded = [];

    public function specialties()
    {
        return $this->hasMany(Specialty::class);
    }
    public function professors()
    {
        return $this->hasMany(Professor::class);
    }
}
