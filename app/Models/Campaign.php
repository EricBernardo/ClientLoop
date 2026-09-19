<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends TenantModel
{
    protected $fillable = ['company_id', 'message_template_id', 'name', 'type', 'status', 'filters', 'starts_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'starts_at' => 'datetime'];
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
