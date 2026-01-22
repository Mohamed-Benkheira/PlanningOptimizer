<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use DepartmentScoped;
    protected $guarded = [];
    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}

