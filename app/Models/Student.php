<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;
    use DepartmentScoped;
    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }
}

