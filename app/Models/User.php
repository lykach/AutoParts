<?php

namespace App\Models;

use App\Rules\UkrainianPhone;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar_url',
        'user_group_id', // ✅ Група користувача (знижки/націнки)
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ✅ Доступ до адмін-панелі
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole(['super-admin', 'admin', 'manager']);
        }

        return true;
    }

    /**
     * ✅ Аватар для Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    /**
     * ✅ Accessor: форматований телефон (для виводу)
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        return UkrainianPhone::format($this->phone);
    }

    /**
     * ✅ Mutator: нормалізація телефону перед збереженням
     */
    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = UkrainianPhone::normalize($value);
    }

    /**
     * ✅ Relation: група користувача
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }
}
