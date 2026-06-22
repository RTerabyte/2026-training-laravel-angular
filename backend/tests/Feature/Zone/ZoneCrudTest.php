<?php

namespace Tests\Feature\Zone;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ZoneCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_zones_requires_authentication(): void
    {
        $response = $this->postJson('/api/zones', [
            'restaurant_id' => 1,
            'name' => 'Terraza',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_zone(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $response = $this->postJson('/api/zones', [
            'restaurant_id' => $restaurantId,
            'name' => 'Terraza Test',
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'Terraza Test',
        ]);

        $this->assertDatabaseHas('zones', [
            'restaurant_id' => $restaurantId,
            'name' => 'Terraza Test',
        ]);
    }

    public function test_authenticated_user_can_list_zones(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/zones', [
            'restaurant_id' => $restaurantId,
            'name' => 'Salon Test',
        ])->assertStatus(201);

        $response = $this->getJson('/api/zones');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Salon Test',
        ]);
    }

    public function test_authenticated_user_can_get_zone(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/zones', [
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Detalle',
        ])->assertStatus(201);

        $zone = DB::table('zones')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Zona Detalle')
            ->first();

        $this->assertNotNull($zone);

        $response = $this->getJson('/api/zones/' . $zone->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Zona Detalle',
        ]);
    }

    public function test_authenticated_user_can_update_zone(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/zones', [
            'restaurant_id' => $restaurantId,
            'name' => 'Terraza Test',
        ])->assertStatus(201);

        $zone = DB::table('zones')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Terraza Test')
            ->first();

        $this->assertNotNull($zone);

        $response = $this->putJson('/api/zones/' . $zone->uuid, [
            'name' => 'Terraza Actualizada',
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Terraza Actualizada',
        ]);

        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'restaurant_id' => $restaurantId,
            'name' => 'Terraza Actualizada',
        ]);
    }

    public function test_authenticated_user_can_delete_zone(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/zones', [
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Para Borrar',
        ])->assertStatus(201);

        $zone = DB::table('zones')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Zona Para Borrar')
            ->first();

        $this->assertNotNull($zone);

        $response = $this->deleteJson('/api/zones/' . $zone->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('zones', [
            'id' => $zone->id,
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
            'email' => 'admin-zone-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-zone-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
