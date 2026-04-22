<?php

namespace Database\Seeders;

use App\Domain\Assistant\Models\TypeCatalog;
use Illuminate\Database\Seeder;

class TypeCatalogSeeder extends Seeder
{
    private const TYPES = [
        'event' => [
            'meeting', 'birthday', 'appointment', 'task', 'reminder', 'workout', 'travel', 'other',
        ],
        'list' => [
            'shopping', 'todo', 'watchlist', 'readlist', 'general',
        ],
        'expense' => [
            'food', 'transport', 'entertainment', 'health', 'education', 'housing', 'clothing', 'other',
        ],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $domain => $names) {
            foreach ($names as $name) {
                TypeCatalog::firstOrCreate(
                    ['user_id' => null, 'domain' => $domain, 'name' => $name],
                );
            }
        }
    }
}
