<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactTask extends TenantModel
{
    protected $fillable = ['company_id', 'customer_id', 'appointment_id', 'opportunity_id', 'campaign_id', 'message_template_id', 'type', 'cycle_key', 'status', 'priority', 'due_at', 'rendered_message', 'outcome', 'outcome_note', 'completed_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ContactAttempt::class);
    }

    public function whatsappUrl(): ?string
    {
        return $this->customer->can_contact ? 'https://wa.me/'.preg_replace('/\D/', '', $this->customer->phone).'?text='.rawurlencode($this->rendered_message ?? '') : null;
    }
}
