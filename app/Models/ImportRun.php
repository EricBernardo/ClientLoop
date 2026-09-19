<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRun extends TenantModel
{
    protected $fillable = ['company_id', 'user_id', 'type', 'status', 'path', 'mapping', 'created_count', 'updated_count', 'errors'];

    protected function casts(): array
    {
        return ['errors' => 'array', 'mapping' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
