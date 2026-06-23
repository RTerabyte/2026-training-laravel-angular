<?php

namespace Tests\Feature\Table;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TableCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_tables_requires_authentication(): void
    {
        $response = $this->postJson('/api/tables', [
            'restaurant_id' => '1',
            'zone_id' => (string) Str::uuid(),
            'name' => 'Mesa 1',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_table(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $response = $this->postJson('/api/tables', [
            'restaurant_id' => (string) $restaurantId,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa 1 Test',
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'Mesa 1 Test',
        ]);

        $this->assertDatabaseHas('tables', [
            'restaurant_id' => $restaurantId,
            'zone_id' => $zone->id,
            'name' => 'Mesa 1 Test',
        ]);
    }

    public function test_authenticated_user_can_list_tables(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/tables', [
            'restaurant_id' => (string) $restaurantId,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa 2 Test',
        ])->assertStatus(201);

        $response = $this->getJson('/api/tables');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Mesa 2 Test',
        ]);
    }

    public function test_authenticated_user_can_get_table(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/tables', [
            'restaurant_id' => (string) $restaurantId,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa Detalle',
        ])->assertStatus(201);

        $table = DB::table('tables')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Mesa Detalle')
            ->first();

        $this->assertNotNull($table);

        $response = $this->getJson('/api/tables/' . $table->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Mesa Detalle',
        ]);
    }

    public function test_authenticated_user_can_update_table(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $newZone = $this->createZone($restaurantId, 'Salon Interior');

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/tables', [
            'restaurant_id' => (string) $restaurantId,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa 1 Test',
        ])->assertStatus(201);

        $table = DB::table('tables')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Mesa 1 Test')
            ->first();

        $this->assertNotNull($table);

        $response = $this->putJson('/api/tables/' . $table->uuid, [
            'zone_id' => $newZone->uuid,
            'name' => 'Mesa 1 Actualizada',
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Mesa 1 Actualizada',
        ]);

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'restaurant_id' => $restaurantId,
            'zone_id' => $newZone->id,
            'name' => 'Mesa 1 Actualizada',
        ]);
    }

    public function test_authenticated_user_can_delete_table(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/tables', [
            'restaurant_id' => (string) $restaurantId,
            'zone_id' => $zone->uuid,
            'name' => 'Mesa Para Borrar',
        ])->assertStatus(201);

        $table = DB::table('tables')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Mesa Para Borrar')
            ->first();

        $this->assertNotNull($table);

        $response = $this->deleteJson('/api/tables/' . $table->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('tables', [
            'id' => $table->id,
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
            'email' => 'admin-table-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-table-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createZone(int $restaurantId, string $name = 'Terraza Test'): object
    {
        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('zones')->where('id', $zoneId)->first();
    }
}
