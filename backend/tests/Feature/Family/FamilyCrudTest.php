<?php

namespace Tests\Feature\Family;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_families_requires_authentication(): void
    {
        $response = $this->postJson('/api/families', [
            'restaurant_id' => 1,
            'name' => 'Entrantes',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_family(): void
    {
        $restaurantId = $this->createRestaurant();

        $user = EloquentUser::create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => 'admin',
            'image_src' => null,
            'name' => 'Admin Test',
            'email' => 'admin-family-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'pin' => '1234',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/families', [
            'restaurant_id' => $restaurantId,
            'name' => 'Entrantes Test',
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'Entrantes Test',
        ]);

        $this->assertDatabaseHas('families', [
            'restaurant_id' => $restaurantId,
            'name' => 'Entrantes Test',
        ]);
    }

    private function createRestaurant(): int
    {
        return DB::table('restaurants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurante Test',
            'legal_name' => 'Restaurante Test SL',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => 'restaurant-family-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}