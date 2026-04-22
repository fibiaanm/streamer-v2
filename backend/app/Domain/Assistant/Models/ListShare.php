<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListShare extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'list_shares';

    public $timestamps = false;

    protected $fillable = [
        'list_id',
        'shared_with_user_id',
        'invited_by_user_id',
        'permission',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(AssistantList::class, 'list_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
