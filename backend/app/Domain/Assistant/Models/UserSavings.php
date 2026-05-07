<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSavings extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'user_savings';

    protected $fillable = ['user_id', 'balance_cents', 'currency'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
