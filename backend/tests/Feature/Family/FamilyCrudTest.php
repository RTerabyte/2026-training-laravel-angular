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

        $this->authenticateAsAdmin($restaurantId);

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


    public function test_authenticated_user_can_list_families(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/families', [
            'restaurant_id' => $restaurantId,
            'name' => 'Bebidas Test',
        ])->assertStatus(201);

        $response = $this->getJson('/api/families');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Bebidas Test',
        ]);
    }

    public function test_authenticated_user_can_get_family(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/families', [
            'restaurant_id' => $restaurantId,
            'name' => 'Familia Detalle',
        ])->assertStatus(201);

        $family = DB::table('families')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Familia Detalle')
            ->first();

        $this->assertNotNull($family);

        $response = $this->getJson('/api/families/' . $family->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Familia Detalle',
        ]);
    }

    public function test_authenticated_user_can_update_family(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/families', [
            'restaurant_id' => $restaurantId,
            'name' => 'Entrantes Test',
        ])->assertStatus(201);

        $family = DB::table('families')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Entrantes Test')
            ->first();

        $this->assertNotNull($family);

        $response = $this->putJson('/api/families/' . $family->uuid, [
            'name' => 'Entrantes Actualizados',
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Entrantes Actualizados',
        ]);

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'restaurant_id' => $restaurantId,
            'name' => 'Entrantes Actualizados',
        ]);
    }

    public function test_authenticated_user_can_delete_family(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/families', [
            'restaurant_id' => $restaurantId,
            'name' => 'Familia Para Borrar',
        ])->assertStatus(201);

        $family = DB::table('families')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Familia Para Borrar')
            ->first();

        $this->assertNotNull($family);

        $response = $this->deleteJson('/api/families/' . $family->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('families', [
            'id' => $family->id,
        ]);
    }

    private function authenticateAsAdmin(int $restaurantId): void
    {
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
