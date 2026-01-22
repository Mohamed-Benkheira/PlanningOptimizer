<?php

namespace App\Models;

use App\Models\Traits\DepartmentScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Module extends Model
{
    use DepartmentScoped;
    use HasFactory;
    protected $guarded = [];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'module_group');
    }
}
