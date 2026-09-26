<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends TenantModel
{
    protected $fillable = ['company_id', 'campaign_id', 'customer_id', 'status', 'contact_task_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contactTask(): BelongsTo
    {
        return $this->belongsTo(ContactTask::class);
    }
}
