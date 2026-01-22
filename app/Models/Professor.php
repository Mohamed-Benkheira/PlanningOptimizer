<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Professor extends Model
{
    use DepartmentScoped;
    use HasFactory;
    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_professor');
    }
}
