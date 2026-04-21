<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enterprise extends Model
{
    use HasFactory, HasHashId, SoftDeletes;

    protected $fillable = ['name', 'type', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EnterpriseMember::class);
    }

    public function enterpriseProducts(): HasMany
    {
        return $this->hasMany(EnterpriseProduct::class);
    }

    public function createEnterpriseProduct(Plan $plan): EnterpriseProduct
    {
        return EnterpriseProduct::create([
            'enterprise_id' => $this->id,
            'plan_id'       => $plan->id,
            'product_id'    => $plan->product_id,
            'status'        => 'active',
            'starts_at'     => now(),
        ]);
    }
}
