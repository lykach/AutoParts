<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleAnalog extends Model
{
    protected $table = 'article_analogs';

    protected $fillable = [
        'article',
        'manufacturer_article',
        'analog',
        'manufacturer_analog',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Нормалізація для стабільних матчів та уникнення дублів
            $model->article = self::norm($model->article);
            $model->manufacturer_article = self::norm($model->manufacturer_article);

            $model->analog = self::norm($model->analog);
            $model->manufacturer_analog = self::norm($model->manufacturer_analog);
        });
    }

    private static function norm(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_strtoupper($value);
    }
}
