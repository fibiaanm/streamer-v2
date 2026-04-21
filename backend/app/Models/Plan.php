<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    use HasFactory, HasHashId;

    protected $fillable = ['product_id', 'name', 'limits_json', 'is_free', 'price_monthly_cents', 'price_yearly_cents'];

    protected $casts = [
        'limits_json' => 'array',
        'is_free'     => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function freeFor(string $productSlug): self
    {
        return static::where('is_free', true)
            ->whereHas('product', fn ($q) => $q->where('slug', $productSlug))
            ->firstOrFail();
    }
}
