<?php

namespace App\Models;

class Service extends TenantModel
{
    protected $fillable = ['company_id', 'name', 'suggested_price', 'return_interval_months', 'duration_minutes', 'active'];

    protected function casts(): array
    {
        return ['suggested_price' => 'decimal:2', 'duration_minutes' => 'integer', 'active' => 'boolean'];
    }
}
