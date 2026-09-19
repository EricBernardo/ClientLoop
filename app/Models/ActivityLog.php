<?php

namespace App\Models;

class ActivityLog extends TenantModel
{
    protected $fillable = ['company_id', 'user_id', 'event', 'subject_type', 'subject_id', 'properties'];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }
}
