<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class TenantModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $q): void {
            $u = auth()->user();
            if ($u && ! $u->is_super_admin && $u->company_id) {
                $q->where($q->getModel()->getTable().'.company_id', $u->company_id);
            }
        });
        static::creating(function (self $m): void {
            if (! $m->company_id && auth()->check()) {
                $m->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
