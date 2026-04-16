<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasHashId, SoftDeletes;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function enterpriseMembers(): HasMany
    {
        return $this->hasMany(EnterpriseMember::class);
    }

    public function createEnterprise(string $name, string $type = 'personal'): Enterprise
    {
        return Enterprise::create([
            'name'     => $name,
            'type'     => $type,
            'owner_id' => $this->id,
        ]);
    }

    public function assignOwnerRole(Enterprise $enterprise): EnterpriseMember
    {
        $ownerRole = EnterpriseRole::where('name', 'owner')
            ->whereNull('enterprise_id')
            ->firstOrFail();

        return EnterpriseMember::create([
            'user_id'       => $this->id,
            'enterprise_id' => $enterprise->id,
            'role_id'       => $ownerRole->id,
            'status'        => 'active',
        ]);
    }
}
