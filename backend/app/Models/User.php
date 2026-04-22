<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use HasFactory, HasHashId, InteractsWithMedia, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'timezone', 'default_currency', 'username', 'friend_code'];

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(50)
            ->height(50)
            ->format('jpg')
            ->nonQueued()
            ->performOnCollections('avatar');

        $this->addMediaConversion('thumb_webp')
            ->width(50)
            ->height(50)
            ->format('webp')
            ->nonQueued()
            ->performOnCollections('avatar');
    }

    public function getAvatarUrls(): array
    {
        return [
            'jpeg' => $this->getFirstMediaUrl('avatar', 'thumb'),
            'webp' => $this->getFirstMediaUrl('avatar', 'thumb_webp'),
        ];
    }

    public function personalEnterprise(): HasOne
    {
        return $this->hasOne(Enterprise::class, 'owner_id')->where('type', 'personal');
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
