<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnterpriseRole extends Model
{
    use HasHashId;

    public $timestamps = false;

    protected $fillable = ['enterprise_id', 'name', 'is_default'];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            EnterprisePermission::class,
            'enterprise_role_permissions',
            'role_id',
            'permission_id',
        );
    }

    public function isGlobal(): bool
    {
        return $this->enterprise_id === null;
    }

    public function isOwner(): bool
    {
        return $this->name === 'owner' && $this->isGlobal();
    }
}
