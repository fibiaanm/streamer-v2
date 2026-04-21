<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, HasHashId, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'owner_user_id',
        'parent_workspace_id',
        'name',
        'status',
        'path',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'parent_workspace_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Workspace::class, 'parent_workspace_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(WorkspaceRole::class);
    }

    public function isOrphaned(): bool
    {
        return $this->status === 'orphaned';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
