<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory, HasHashId;

    protected $fillable = ['name', 'type', 'limits_json', 'is_free', 'price_monthly_cents', 'price_yearly_cents'];

    protected $casts = [
        'limits_json' => 'array',
        'is_free'     => 'boolean',
    ];

    public static function free(): self
    {
        return static::where('is_free', true)->where('type', 'individual')->firstOrFail();
    }
}
