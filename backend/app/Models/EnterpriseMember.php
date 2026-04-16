<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseMember extends Model
{
    use HasFactory, HasHashId;

    protected $fillable = ['user_id', 'enterprise_id', 'role_id', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(EnterpriseRole::class, 'role_id');
    }
}
