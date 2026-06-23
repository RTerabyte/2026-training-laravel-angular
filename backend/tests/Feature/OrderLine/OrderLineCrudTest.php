<?php

namespace Tests\Feature\OrderLine;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderLineCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_order_lines_requires_authentication(): void
    {
        $response = $this->postJson('/api/order-lines', [
            'restaurant_id' => 1,
            'order_id' => (string) Str::uuid(),
            'product_id' => (string) Str::uuid(),
            'user_id' => (string) Str::uuid(),
            'quantity' => 2,
            'price' => 250,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_order_line(): void
    {
        [$restaurantId, $user, $order, $product] = $this->prepareOrderLineData();

        $response = $this->postJson('/api/order-lines', [
            'restaurant_id' => $restaurantId,
            'order_id' => $order->uuid,
            'product_id' => $product->uuid,
            'user_id' => $user->uuid,
            'quantity' => 2,
            'price' => 250,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'quantity' => 2,
            'price' => 250,
        ]);

        $this->assertDatabaseHas('order_lines', [
            'restaurant_id' => $restaurantId,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'price' => 250,
            'tax_percentage' => 21,
        ]);
    }

    public function test_authenticated_user_can_list_order_lines(): void
    {
        [$restaurantId, $user, $order, $product] = $this->prepareOrderLineData();

        $this->createOrderLine($restaurantId, $order, $product, $user, 3, 300);

        $response = $this->getJson('/api/order-lines');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'quantity' => 3,
            'price' => 300,
        ]);
    }

    public function test_authenticated_user_can_get_order_line(): void
    {
        [$restaurantId, $user, $order, $product] = $this->prepareOrderLineData();

        $orderLine = $this->createOrderLine($restaurantId, $order, $product, $user, 4, 400);

        $response = $this->getJson('/api/order-lines/' . $orderLine->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'quantity' => 4,
            'price' => 400,
        ]);
    }

    public function test_authenticated_user_can_update_order_line(): void
    {
        [$restaurantId, $user, $order, $product] = $this->prepareOrderLineData();

        $orderLine = $this->createOrderLine($restaurantId, $order, $product, $user, 2, 250);

        $response = $this->putJson('/api/order-lines/' . $orderLine->uuid, [
            'quantity' => 5,
            'price' => 500,
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'quantity' => 5,
            'price' => 500,
        ]);

        $this->assertDatabaseHas('order_lines', [
            'id' => $orderLine->id,
            'quantity' => 5,
            'price' => 500,
        ]);
    }

    public function test_authenticated_user_can_delete_order_line(): void
    {
        [$restaurantId, $user, $order, $product] = $this->prepareOrderLineData();

        $orderLine = $this->createOrderLine($restaurantId, $order, $product, $user, 2, 250);

        $response = $this->deleteJson('/api/order-lines/' . $orderLine->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('order_lines', [
            'id' => $orderLine->id,
        ]);
    }

    private function prepareOrderLineData(): array
    {
        $restaurantId = $this->createRestaurant();

        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);

        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);
        $product = $this->createProduct($restaurantId, $family, $tax);
        $order = $this->createOrder($restaurantId, $table, $user);

        return [$restaurantId, $user, $order, $product];
    }

    private function createOrderLine(
        int $restaurantId,
        object $order,
        object $product,
        EloquentUser $user,
        int $quantity,
        int $price,
    ): object {
        $this->postJson('/api/order-lines', [
            'restaurant_id' => $restaurantId,
            'order_id' => $order->uuid,
            'product_id' => $product->uuid,
            'user_id' => $user->uuid,
            'quantity' => $quantity,
            'price' => $price,
            'tax_percentage' => 21,
        ])->assertStatus(201);

        return DB::table('order_lines')
            ->where('restaurant_id', $restaurantId)
            ->where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->where('quantity', $quantity)
            ->where('price', $price)
            ->first();
    }

    private function createOrder(int $restaurantId, object $table, EloquentUser $user): object
    {
        $this->postJson('/api/orders', [
            'restaurant_id' => $restaurantId,
            'table_id' => $table->uuid,
            'opened_by_user_id' => $user->uuid,
            'diners' => 2,
        ])->assertStatus(201);

        return DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->where('table_id', $table->id)
            ->first();
    }

    private function createProduct(int $restaurantId, object $family, object $tax): object
    {
        $this->postJson('/api/products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'stock' => 10,
            'image_src' => null,
            'name' => 'Producto Order Line Test',
            'price' => 250,
        ])->assertStatus(201);

        return DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Producto Order Line Test')
            ->first();
    }

    private function authenticateAsAdmin(int $restaurantId): EloquentUser
    {
        $user = EloquentUser::create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => 'admin',
            'image_src' => null,
            'name' => 'Admin Test',
            'email' => 'admin-order-line-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-order-line-' . Str::uuid() . '@test.com',
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
            'name' => 'Zona Test',
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
            'name' => 'Mesa Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tables')->where('id', $tableId)->first();
    }

    private function createFamily(int $restaurantId): object
    {
        $familyId = DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'name' => 'Familia Test',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('families')->where('id', $familyId)->first();
    }

    private function createTax(int $restaurantId): object
    {
        $taxId = DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Test',
            'percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('taxes')->where('id', $taxId)->first();
    }
}