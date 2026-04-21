<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invitation extends Model
{
    use HasHashId;

    protected $fillable = [
        'invitable_type',
        'invitable_id',
        'invited_by_user_id',
        'email',
        'enterprise_role_id',
        'workspace_role_id',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function invitable(): MorphTo
    {
        return $this->morphTo();
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function enterpriseRole(): BelongsTo
    {
        return $this->belongsTo(EnterpriseRole::class, 'enterprise_role_id');
    }

    public function workspaceRole(): BelongsTo
    {
        return $this->belongsTo(WorkspaceRole::class, 'workspace_role_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }
}
