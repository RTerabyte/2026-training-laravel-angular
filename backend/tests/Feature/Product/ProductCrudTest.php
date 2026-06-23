<?php

namespace Tests\Feature\Product;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_products_requires_authentication(): void
    {
        $response = $this->postJson('/api/products', [
            'restaurant_id' => 1,
            'family_id' => (string) Str::uuid(),
            'tax_id' => (string) Str::uuid(),
            'stock' => 10,
            'image_src' => null,
            'name' => 'Coca Cola',
            'price' => 250,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_product(): void
    {
        $restaurantId = $this->createRestaurant();
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $response = $this->postJson('/api/products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'stock' => 10,
            'image_src' => null,
            'name' => 'Coca Cola Test',
            'price' => 250,
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'Coca Cola Test',
            'price' => 250,
            'stock' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->id,
            'tax_id' => $tax->id,
            'name' => 'Coca Cola Test',
            'price' => 250,
            'stock' => 10,
        ]);
    }

    public function test_authenticated_user_can_list_products(): void
    {
        $restaurantId = $this->createRestaurant();
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'stock' => 15,
            'image_src' => null,
            'name' => 'Producto Listado',
            'price' => 300,
        ])->assertStatus(201);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Producto Listado',
        ]);
    }

    public function test_authenticated_user_can_get_product(): void
    {
        $restaurantId = $this->createRestaurant();
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'stock' => 20,
            'image_src' => null,
            'name' => 'Producto Detalle',
            'price' => 450,
        ])->assertStatus(201);

        $product = DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Producto Detalle')
            ->first();

        $this->assertNotNull($product);

        $response = $this->getJson('/api/products/' . $product->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Producto Detalle',
        ]);
    }

    public function test_authenticated_user_can_update_product(): void
    {
        $restaurantId = $this->createRestaurant();
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'stock' => 10,
            'image_src' => null,
            'name' => 'Producto Original',
            'price' => 250,
        ])->assertStatus(201);

        $product = DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Producto Original')
            ->first();

        $this->assertNotNull($product);

        $response = $this->putJson('/api/products/' . $product->uuid, [
            'name' => 'Producto Actualizado',
            'price' => 500,
            'stock' => 30,
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Producto Actualizado',
            'price' => 500,
            'stock' => 30,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'restaurant_id' => $restaurantId,
            'name' => 'Producto Actualizado',
            'price' => 500,
            'stock' => 30,
        ]);
    }

    public function test_authenticated_user_can_delete_product(): void
    {
        $restaurantId = $this->createRestaurant();
        $family = $this->createFamily($restaurantId);
        $tax = $this->createTax($restaurantId);

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/products', [
            'restaurant_id' => $restaurantId,
            'family_id' => $family->uuid,
            'tax_id' => $tax->uuid,
            'stock' => 10,
            'image_src' => null,
            'name' => 'Producto Para Borrar',
            'price' => 250,
        ])->assertStatus(201);

        $product = DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'Producto Para Borrar')
            ->first();

        $this->assertNotNull($product);

        $response = $this->deleteJson('/api/products/' . $product->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
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
            'email' => 'admin-product-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-product-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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