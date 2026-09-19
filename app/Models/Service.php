<?php

namespace App\Models;

class Service extends TenantModel
{
    protected $fillable = ['company_id', 'name', 'suggested_price', 'return_interval_months', 'active'];

    protected function casts(): array
    {
        return ['suggested_price' => 'decimal:2', 'active' => 'boolean'];
    }
}
