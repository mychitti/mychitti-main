<?php

namespace App\Modules\School\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Scopes a school model to the active branch.
 *
 * - Reads/queries are filtered to the active branch when one is selected
 *   (owner picks it; branch staff are pinned to theirs). No branch selected = all branches.
 * - New records inherit the active branch_id automatically.
 *
 * Apply only to per-branch "people & transaction" models (students, fees, attendance,
 * admissions, certificates). Academic catalogue (classes, subjects, sessions) stays store-wide.
 */
trait BranchScoped
{
    public static function bootBranchScoped(): void
    {
        static::addGlobalScope('schoolBranch', function (Builder $builder) {
            $branchId = school_active_branch_id();
            if ($branchId) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $branchId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->branch_id)) {
                $model->branch_id = school_active_branch_id();
            }
        });
    }
}
