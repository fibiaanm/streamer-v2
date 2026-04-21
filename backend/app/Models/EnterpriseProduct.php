<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseProduct extends Model
{
    use HasHashId, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'plan_id',
        'product_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'provider',
        'provider_customer_id',
        'provider_subscription_id',
        'override_json',
        'amount_paid_cents',
        'currency',
        'discount_pct',
    ];

    protected $casts = [
        'override_json' => 'array',
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
