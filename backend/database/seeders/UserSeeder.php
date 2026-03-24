<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       DB::table('users')->insert([
    [
        'uuid' => Str::uuid()->toString(),
        'role' => 'admin',
        'image_src' => null,
        'name'=> 'admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('admin'),
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'uuid' => Str::uuid()->toString(),
        'role' => 'operator',
        'image_src' => null,
        'name'=> 'user2',
        'email' => 'user2@example.com',
        'password' => Hash::make('user2'),
        'created_at' => now(),
        'updated_at' => now(),
    ],[
        'uuid' => Str::uuid()->toString(),
        'role' => 'operator',
        'image_src' => null,
        'name'=> 'user3',
        'email' => 'user3@example.com',
        'password' => Hash::make('user3'),
        'created_at' => now(),
        'updated_at' => now(),
    ]
]);
    }
}
