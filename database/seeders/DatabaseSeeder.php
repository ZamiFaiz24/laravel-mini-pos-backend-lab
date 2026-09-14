<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory(2)->sequence(
            [
                'name' => 'Cashier Women',
                'email' => 'cashier1@example.com',
                'role' => 'cashier',
            ],
            [
                'name' => 'Cashier Men',
                'email' => 'cashier2@example.com',
                'role' => 'cashier',
            ],
        )->create();

        $categories = Category::factory()
            ->count(8)
            ->create();

        $suppliers = Supplier::factory()
            ->count(8)
            ->create();

        Product::factory()
            ->count(30)
            ->recycle($categories)
            ->recycle($suppliers)
            ->create();
    }
}
