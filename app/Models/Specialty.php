<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
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

