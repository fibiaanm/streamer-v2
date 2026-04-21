<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'        => 'Core',
                'slug'        => 'core',
                'description' => 'Workspaces, streaming y rooms',
            ],
            [
                'name'        => 'Assistant',
                'slug'        => 'assistant',
                'description' => 'Asistente de IA',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['slug' => $product['slug']], $product);
        }
    }
}
