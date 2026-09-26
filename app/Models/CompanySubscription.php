<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscription extends TenantModel
{
    protected $fillable = ['company_id', 'plan_id', 'status', 'starts_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
