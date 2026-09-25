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

    /** @return array<int|string, string> */
    public function displayErrors(): array
    {
        return collect($this->errors ?? [])
            ->map(function (mixed $message): string {
                $message = (string) $message;

                if (str_contains($message, 'SplFileObject::__construct') && str_contains($message, 'Failed to open stream')) {
                    return 'O arquivo enviado não estava disponível para o processamento. Envie o CSV novamente.';
                }

                return $message;
            })
            ->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
