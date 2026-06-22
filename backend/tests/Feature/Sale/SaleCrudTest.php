<?php

namespace Tests\Feature\Sale;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_sales_requires_authentication(): void
    {
        $response = $this->postJson('/api/sales', [
            'restaurant_id' => 1,
            'order_id' => (string) Str::uuid(),
            'user_id' => 1,
            'total' => 500,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_sale(): void
    {
        [$restaurantId, $user, $order] = $this->prepareSaleData('1001');

        $response = $this->postJson('/api/sales', [
            'restaurant_id' => $restaurantId,
            'order_id' => $order->uuid,
            'user_id' => (int) $user->uuid,
            'total' => 500,
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'total' => 500,
            'ticket_number' => 1,
        ]);

        $this->assertDatabaseHas('sales', [
            'restaurant_id' => $restaurantId,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'total' => 500,
            'ticket_number' => 1,
        ]);
    }

    public function test_authenticated_user_can_list_sales(): void
    {
        [$restaurantId, $user, $order] = $this->prepareSaleData('1002');

        $this->createSale($restaurantId, $order, $user, 750);

        $response = $this->getJson('/api/sales?restaurant_id=' . $restaurantId);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'total' => 750,
        ]);
    }

    public function test_authenticated_user_can_list_sales_by_date(): void
    {
        [$restaurantId, $user, $order] = $this->prepareSaleData('1003');

        $this->createSale($restaurantId, $order, $user, 900);

        $response = $this->getJson('/api/sales?restaurant_id=' . $restaurantId . '&date=' . now()->toDateString());

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'total' => 900,
        ]);
    }

    public function test_authenticated_user_can_get_sale(): void
    {
        [$restaurantId, $user, $order] = $this->prepareSaleData('1004');

        $sale = $this->createSale($restaurantId, $order, $user, 650);

        $response = $this->getJson('/api/sales/' . $sale->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'total' => 650,
        ]);
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders/' . Str::uuid() . '/checkout', [
            'restaurant_id' => 1,
            'user_id' => (string) Str::uuid(),
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_checkout_order(): void
    {
        [$restaurantId, $user, $order, $orderLine] = $this->prepareCheckoutData();

        $response = $this->postJson('/api/orders/' . $order->uuid . '/checkout', [
            'restaurant_id' => $restaurantId,
            'user_id' => $user->uuid,
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'total' => 500,
        ]);

        $sale = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('order_id', $order->id)
            ->first();

        $this->assertNotNull($sale);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'restaurant_id' => $restaurantId,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'total' => 500,
        ]);

        $this->assertDatabaseHas('sales_lines', [
            'restaurant_id' => $restaurantId,
            'sale_id' => $sale->id,
            'order_line_id' => $orderLine->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'price' => 250,
            'tax_percentage' => 21,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'invoiced',
            'closed_by_user_id' => $user->id,
        ]);
    }

    private function prepareSaleData(string $userUuid): array
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId, $userUuid);
        $order = $this->createOrder($restaurantId, $table, $user);

        return [$restaurantId, $user, $order];
    }

    private function prepareCheckoutData(): array
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);
        $product = $this->createProduct($restaurantId, $family, $tax);
        $order = $this->createOrder($restaurantId, $table, $user);
        $orderLine = $this->createOrderLine($restaurantId, $order, $product, $user, 2, 250);

        return [$restaurantId, $user, $order, $orderLine];
    }

    private function createSale(int $restaurantId, object $order, EloquentUser $user, int $total): object
    {
        $saleId = DB::table('sales')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'ticket_number' => 1,
            'value_date' => now(),
            'total' => $total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('sales')->where('id', $saleId)->first();
    }

    private function createOrderLine(
        int $restaurantId,
        object $order,
        object $product,
        EloquentUser $user,
        int $quantity,
        int $price,
    ): object {
        $orderLineId = DB::table('order_lines')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => $quantity,
            'price' => $price,
            'tax_percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('order_lines')->where('id', $orderLineId)->first();
    }

    private function createOrder(int $restaurantId, object $table, EloquentUser $user): object
    {
        $orderId = DB::table('orders')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'status' => 'open',
            'table_id' => $table->id,
            'opened_by_user_id' => $user->id,
            'closed_by_user_id' => null,
            'diners' => 2,
            'opened_at' => now(),
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('orders')->where('id', $orderId)->first();
    }

    private function createProduct(int $restaurantId, object $family, object $tax): object
    {
        $productId = DB::table('products')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'stock' => 10,
            'image_src' => null,
            'name' => 'Producto Sale Test',
            'price' => 250,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('products')->where('id', $productId)->first();
    }

    private function createFamily(int $restaurantId): object
    {
        $familyId = DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'name' => 'Familia Sale Test',
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
            'name' => 'IVA Sale Test',
            'percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('taxes')->where('id', $taxId)->first();
    }

    private function createZone(int $restaurantId): object
    {
        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Sale Test',
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
            'name' => 'Mesa Sale Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tables')->where('id', $tableId)->first();
    }

    private function authenticateAsAdmin(int $restaurantId, ?string $uuid = null): EloquentUser
    {
        $user = EloquentUser::create([
            'uuid' => $uuid ?? (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => 'admin',
            'image_src' => null,
            'name' => 'Admin Test',
            'email' => 'admin-sale-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-sale-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
