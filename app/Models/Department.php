<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
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
