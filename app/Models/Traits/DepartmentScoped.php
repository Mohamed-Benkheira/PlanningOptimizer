<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait DepartmentScoped
{
    protected static function bootDepartmentScoped(): void
    {
        static::addGlobalScope('department_scope', function (Builder $query) {
            $user = auth()->user();

            // Only apply to department heads with assigned department
            if (!$user || !$user->isDepartmentHead() || !$user->department_id) {
                return;
            }

            $model = $query->getModel();
            $table = $model->getTable();

            // Apply filtering based on table/model
            self::applyDepartmentFilter($query, $table, $user->department_id);
        });
    }

    /**
     * Apply department filter based on table relationships
     */
    protected static function applyDepartmentFilter(Builder $query, string $table, int $departmentId): void
    {
        match ($table) {
            // Direct department_id column
            'departments' => $query->where('id', $departmentId),
            'specialties' => $query->where('department_id', $departmentId),
            'professors' => $query->where('department_id', $departmentId),

            // Through specialty relationship
            'modules' => $query->whereHas('specialty', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                }),
            'groups' => $query->whereHas('specialty', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                }),

            // Through group -> specialty relationship
            'students' => $query->whereHas('group', function ($q) use ($departmentId) {
                    $q->whereHas('specialty', function ($sq) use ($departmentId) {
                        $sq->where('department_id', $departmentId);
                    });
                }),
            'inscriptions' => $query->whereHas('student.group.specialty', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                }),

            // Through module -> specialty relationship
            'scheduled_exams' => $query->whereHas('module', function ($q) use ($departmentId) {
                    $q->whereHas('specialty', function ($sq) use ($departmentId) {
                        $sq->where('department_id', $departmentId);
                    });
                }),

            // Through scheduled_exam -> module -> specialty relationship
            'scheduled_exam_rooms' => $query->whereHas('scheduledExam', function ($q) use ($departmentId) {
                    $q->whereHas('module', function ($mq) use ($departmentId) {
                        $mq->whereHas('specialty', function ($sq) use ($departmentId) {
                            $sq->where('department_id', $departmentId);
                        });
                    });
                }),

            // No filtering for university-wide resources
            default => null
        };
    }

    /**
     * Query without department scope
     */
    public function scopeWithoutDepartmentScope($query)
    {
        return $query->withoutGlobalScope('department_scope');
    }

    /**
     * Check if current user can bypass department scope
     */
    public static function canBypassDepartmentScope(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isExamAdmin()
            || $user->isDean();
    }
}
