<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['company_id', 'name', 'email', 'email_verified_at', 'password', 'is_super_admin', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_super_admin' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'super') {
            return $this->is_super_admin;
        }

        $emailVerified = ! config('clientloop.require_email_verification') || $this->hasVerifiedEmail();

        return ! $this->is_super_admin
            && $emailVerified
            && $this->company
            && in_array($this->company->status, ['trial', 'active'], true);
    }
}
