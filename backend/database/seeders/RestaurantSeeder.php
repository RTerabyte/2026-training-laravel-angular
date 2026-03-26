<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("restaurants")->insert([
            [
                'uuid' => Str::uuid()->toString(),
                'name' => 'Restaurant 1',
                'legal_name'=> 'Restaurant 1 SL',
                'tax_id'=> 'B12345678',
                'email'=> 'restaurant1@example.com',
                'password'=> Hash::make('restaurant1'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
       ]);
    }
}
