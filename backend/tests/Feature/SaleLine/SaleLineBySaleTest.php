<?php

namespace Tests\Feature\SaleLine;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleLineBySaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_sale_lines_by_sale_requires_authentication(): void
    {
        $response = $this->getJson('/api/sales/' . Str::uuid() . '/lines');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_sale_lines_by_sale(): void
    {
        [$sale, $saleLine] = $this->prepareSaleLineData();

        $response = $this->getJson('/api/sales/' . $sale->uuid . '/lines');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'quantity' => $saleLine->quantity,
            'price' => $saleLine->price,
            'tax_percentage' => $saleLine->tax_percentage,
        ]);
    }

    private function prepareSaleLineData(): array
    {
        $restaurantId = $this->createRestaurant();
        $zone = $this->createZone($restaurantId);
        $table = $this->createTable($restaurantId, $zone->id);
        $user = $this->authenticateAsAdmin($restaurantId);
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);
        $product = $this->createProduct($restaurantId, $family, $tax);
        $order = $this->createOrder($restaurantId, $table, $user);
        $orderLine = $this->createOrderLine($restaurantId, $order, $product, $user);
        $sale = $this->createSale($restaurantId, $order, $user, 500);
        $saleLine = $this->createSaleLine($restaurantId, $sale, $orderLine, $user);

        return [$sale, $saleLine];
    }

    private function createSaleLine(
        int $restaurantId,
        object $sale,
        object $orderLine,
        EloquentUser $user,
    ): object {
        $saleLineId = DB::table('sales_lines')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'sale_id' => $sale->id,
            'order_line_id' => $orderLine->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'price' => 250,
            'tax_percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('sales_lines')->where('id', $saleLineId)->first();
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
    ): object {
        $orderLineId = DB::table('order_lines')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'price' => 250,
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
            'name' => 'Producto Sale Line Test',
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
            'name' => 'Familia Sale Line Test',
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
            'name' => 'IVA Sale Line Test',
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
            'name' => 'Zona Sale Line Test',
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
            'name' => 'Mesa Sale Line Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tables')->where('id', $tableId)->first();
    }

    private function authenticateAsAdmin(int $restaurantId): EloquentUser
    {
        $user = EloquentUser::create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => 'admin',
            'image_src' => null,
            'name' => 'Admin Test',
            'email' => 'admin-sale-line-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-sale-line-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
