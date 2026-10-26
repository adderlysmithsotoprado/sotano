<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Comidas', 'type' => 'comida'],
            ['name' => 'Bebidas', 'type' => 'bebida'],
            ['name' => 'Licores', 'type' => 'licor'],
            ['name' => 'Cócteles', 'type' => 'coctel'],
            ['name' => 'Postres', 'type' => 'postre'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'type' => $category['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
