<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AgencyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply scope if user is authenticated and has an agency
        if (auth()->check() && auth()->user()->agency_id) {
            $user = auth()->user();
            
            // Super admin can see all agencies
            if ($user->hasRole('super-admin')) {
                return;
            }

            // Regular users see only their agency data
            $builder->where($model->getTable() . '.agency_id', $user->agency_id);
        }
    }
}
