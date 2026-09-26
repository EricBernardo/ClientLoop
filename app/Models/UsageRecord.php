<?php

namespace App\Models;

class UsageRecord extends TenantModel
{
    protected $fillable = ['company_id', 'period', 'contacts_count', 'tasks_count', 'task_quota_notified_at'];

    protected function casts(): array
    {
        return ['task_quota_notified_at' => 'datetime'];
    }
}
