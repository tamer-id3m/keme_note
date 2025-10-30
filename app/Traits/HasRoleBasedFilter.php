<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Filter\RoleBasedFilterService;

trait HasRoleBasedFilter
{
    /**
     * Apply role-based filtering to the query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeApplyRoleFilter(Builder $query,?User $targetUser = null, bool $strict = true): Builder
    {
        $filterService = app(RoleBasedFilterService::class);
        return $filterService->applyRoleBasedFilter($query, class_basename($this),$targetUser, $strict);
    }
}