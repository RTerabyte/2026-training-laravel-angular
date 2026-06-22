<?php

namespace Tests\Feature\Order;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_orders_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders', [
            'restaurant_id' => 1,
            'table_id' => (string) Str::uuid(),
            'opened_by_user_id' => (string) Str::uuid(),
            'diners' => 2,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_order(): void
    {
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $response = $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 2,
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'diners' => 2,
        ]);

        $this->assertDatabaseHas('orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->id,
            'opened_by_user_id' => $user->id,
            'diners' => 2,
        ]);
    }

    public function test_authenticated_user_can_list_orders(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 3,
        ])->assertStatus(201);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'diners' => 3,
        ]);
    }

    public function test_authenticated_user_can_list_open_orders(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 4,
        ])->assertStatus(201);

        $response = $this->getJson('/api/orders/open?restaurant_id=' . $restaurantId);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'diners' => 4,
        ]);
    }

    public function test_authenticated_user_can_get_order(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 2,
        ])->assertStatus(201);

        $order = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->where('table_id', $table->id)
            ->first();

        $this->assertNotNull($order);

        $response = $this->getJson('/api/orders/' . $order->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'diners' => 2,
        ]);
    }

    public function test_authenticated_user_can_update_order(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 2,
        ])->assertStatus(201);

        $order = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->where('table_id', $table->id)
            ->first();

        $this->assertNotNull($order);

        $response = $this->putJson('/api/orders/' . $order->uuid, [
            'diners' => 5,
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'diners' => 5,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'restaurant_id' => $restaurantId,
            'diners' => 5,
        ]);
    }

    public function test_authenticated_user_can_delete_order(): void
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 2,
        ])->assertStatus(201);

        $order = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->where('table_id', $table->id)
            ->first();

        $this->assertNotNull($order);

        $response = $this->deleteJson('/api/orders/' . $order->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
        ]);
    }

    private function authenticateAsAdmin(int $restaurantId): EloquentUser
    {
        $user = EloquentUser::create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => 'admin',
            'image_src' => null,
            'name' => 'Admin Test',
            'email' => 'admin-order-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'pin' => '1234',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function createRestaurant(): int
    {
        return DB::table('restaurants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurante Test',
            'legal_name' => 'Restaurante Test SL',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => 'restaurant-order-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createZone(int $restaurantId): object
    {
        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'name' => 'Terraza Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('zones')->where('id', $zoneId)->first();
    }

    private function createTable(int $restaurantId, int $zoneId): object
    {
        $tableId = DB::table('tables')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'zone_id' => $zoneId,
            'name' => 'Mesa 1 Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tables')->where('id', $tableId)->first();
    }
}