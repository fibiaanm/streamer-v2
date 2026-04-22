<?php

namespace App\Domain\Assistant\Models;

use App\Models\User;
use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, HasHashId;

    protected $table = 'expenses';

    protected $fillable = [
        'user_id',
        'amount_cents',
        'currency',
        'description',
        'type',
        'spent_at',
    ];

    protected $casts = [
        'spent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
