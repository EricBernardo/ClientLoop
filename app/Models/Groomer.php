<?php

namespace App\Models;

class Groomer extends TenantModel
{
    protected $fillable = ['company_id', 'name', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
