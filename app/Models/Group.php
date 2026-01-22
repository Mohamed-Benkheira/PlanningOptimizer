<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use DepartmentScoped;
    protected $guarded = [];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function professors()
    {
        return $this->belongsToMany(Professor::class, 'group_professor');
    }
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'module_group');
    }
}
