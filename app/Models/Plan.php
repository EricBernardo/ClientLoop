<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'contact_limit', 'task_limit', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }
}
