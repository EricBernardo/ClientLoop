<?php

namespace App\Models;

use App\Services\TemplateRenderer;

class MessageTemplate extends TenantModel
{
    protected $fillable = ['company_id', 'name', 'type', 'body', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $template): void {
            if ($template->active) {
                app(TemplateRenderer::class)->validate($template->body);
            }
        });
    }
}
