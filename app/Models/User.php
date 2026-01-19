<?php

namespace App\Models;

// 1. Add Filament Imports
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 2. Implement the FilamentUser Interface
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',           // Added: Essential for assigning roles
        'department_id',  // Added: Essential for Department Heads
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_EXAM_ADMIN = 'exam_admin';
    public const ROLE_DEPARTMENT_HEAD = 'department_head';
    public const ROLE_DEAN = 'dean';
    public const ROLE_PROFESSOR = 'professor';
    public const ROLE_STUDENT = 'student';

    // 3. The Access Control Logic
    public function canAccessPanel(Panel $panel): bool
    {
        // Only these roles can log in to the admin panel
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_EXAM_ADMIN,
            self::ROLE_DEPARTMENT_HEAD,
            self::ROLE_DEAN,
        ]);

        // Implicitly BLOCKS: ROLE_PROFESSOR and ROLE_STUDENT
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isExamAdmin(): bool
    {
        return $this->role === self::ROLE_EXAM_ADMIN;
    }

    public function isDean(): bool
    {
        return $this->role === self::ROLE_DEAN;
    }

    public function isDepartmentHead(): bool
    {
        return $this->role === self::ROLE_DEPARTMENT_HEAD;
    }
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }


    // 👇 ADD THIS RELATIONSHIP
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // 👇 ADD HELPER METHOD
    public function canSeeAllDepartments(): bool
    {
        return $this->isSuperAdmin() || $this->isDean() || $this->isExamAdmin();
    }

}
