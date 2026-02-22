<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'discount_percent',
        'markup_percent',
    ];

    /**
     * Користувачі, що належать до цієї групи
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Допоміжний метод для розрахунку ціни (приклад логіки)
     * $price - базова ціна запчастини
     */
    public function calculatePrice(float $price): float
    {
        // Спочатку додаємо націнку, потім віднімаємо знижку
        $withMarkup = $price * (1 + $this->markup_percent / 100);
        return $withMarkup * (1 - $this->discount_percent / 100);
    }
}