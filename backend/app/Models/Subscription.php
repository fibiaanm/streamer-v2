<?php

namespace App\Models;

use App\Traits\HasHashId;
use App\Values\ResolvedLimits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasHashId, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'plan_id',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function resolvedLimits(): ResolvedLimits
    {
        $base     = $this->plan->limits_json ?? [];
        $override = $this->override_json ?? [];

        $result = $base;

        foreach ($override as $key => $values) {
            if (isset($result[$key]) && is_array($values)) {
                $result[$key] = array_merge($result[$key], $values);
            }
        }

        return ResolvedLimits::from($result);
    }
}
