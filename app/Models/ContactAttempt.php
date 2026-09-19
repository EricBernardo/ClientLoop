<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactAttempt extends Model
{
    protected $fillable = ['contact_task_id', 'user_id', 'outcome', 'note', 'attempted_at'];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }
}
