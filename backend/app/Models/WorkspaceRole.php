<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceRole extends Model
{
    use HasHashId, SoftDeletes;

    const UPDATED_AT = null;

    protected $fillable = ['workspace_id', 'name', 'is_base'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isGlobal(): bool
    {
        return $this->workspace_id === null;
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspacePermission::class,
            'workspace_role_permissions',
            'role_id',
            'permission_id',
        );
    }
}
