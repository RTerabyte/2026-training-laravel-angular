<?php

namespace Tests\Feature\Tax;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_taxes_requires_authentication(): void
    {
        $response = $this->postJson('/api/taxes', [
            'restaurant_id' => 1,
            'name' => 'IVA General',
            'percentage' => 21,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_tax(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $response = $this->postJson('/api/taxes', [
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Test',
            'percentage' => 21,
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'IVA Test',
            'percentage' => 21,
        ]);

        $this->assertDatabaseHas('taxes', [
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Test',
            'percentage' => 21,
        ]);
    }

    public function test_authenticated_user_can_list_taxes(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/taxes', [
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Reducido Test',
            'percentage' => 10,
        ])->assertStatus(201);

        $response = $this->getJson('/api/taxes');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'IVA Reducido Test',
            'percentage' => 10,
        ]);
    }

    public function test_authenticated_user_can_get_tax(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/taxes', [
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Detalle',
            'percentage' => 4,
        ])->assertStatus(201);

        $tax = DB::table('taxes')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'IVA Detalle')
            ->first();

        $this->assertNotNull($tax);

        $response = $this->getJson('/api/taxes/' . $tax->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'IVA Detalle',
            'percentage' => 4,
        ]);
    }

    public function test_authenticated_user_can_update_tax(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/taxes', [
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Test',
            'percentage' => 21,
        ])->assertStatus(201);

        $tax = DB::table('taxes')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'IVA Test')
            ->first();

        $this->assertNotNull($tax);

        $response = $this->putJson('/api/taxes/' . $tax->uuid, [
            'name' => 'IVA Actualizado',
            'percentage' => 10,
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'IVA Actualizado',
            'percentage' => 10,
        ]);

        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Actualizado',
            'percentage' => 10,
        ]);
    }

    public function test_authenticated_user_can_delete_tax(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $this->postJson('/api/taxes', [
            'restaurant_id' => $restaurantId,
            'name' => 'IVA Para Borrar',
            'percentage' => 21,
        ])->assertStatus(201);

        $tax = DB::table('taxes')
            ->where('restaurant_id', $restaurantId)
            ->where('name', 'IVA Para Borrar')
            ->first();

        $this->assertNotNull($tax);

        $response = $this->deleteJson('/api/taxes/' . $tax->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('taxes', [
            'id' => $tax->id,
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
            'email' => 'admin-tax-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-tax-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
