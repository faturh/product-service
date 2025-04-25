<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset database (hati-hati dengan foreign key)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Buat user dengan email yang berbeda
        $user = User::create([
            'name' => 'Product User',
            'email' => 'product_user@example.com',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
        ]);
        
        // Buat beberapa produk
        Product::create([
            'name' => 'Laptop',
            'description' => 'Powerful laptop for development',
            'price' => 12000000,
            'stock' => 10
        ]);
        
        Product::create([
            'name' => 'Smartphone',
            'description' => 'Latest smartphone model',
            'price' => 5000000,
            'stock' => 20
        ]);
    }
}