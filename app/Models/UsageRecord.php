<?php

namespace App\Models;

class UsageRecord extends TenantModel
{
    protected $fillable = ['company_id', 'period', 'contacts_count', 'tasks_count'];
}
