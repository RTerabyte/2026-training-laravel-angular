<?php

namespace Tests\Feature\Restaurant;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_restaurants_requires_authentication(): void
    {
        $response = $this->postJson('/api/restaurants', [
            'name' => 'Restaurante Nuevo',
            'legal_name' => 'Restaurante Nuevo SL',
            'tax_id' => 'B12345678',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_restaurant(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $email = 'created-restaurant-' . Str::uuid() . '@test.com';

        $response = $this->postJson('/api/restaurants', [
            'name' => 'Restaurante Creado',
            'legal_name' => 'Restaurante Creado SL',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => $email,
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'Restaurante Creado',
            'email' => $email,
        ]);

        $restaurant = DB::table('restaurants')
            ->where('email', $email)
            ->first();

        $this->assertNotNull($restaurant);
        $this->assertTrue(Hash::check('password123', $restaurant->password));
    }

    public function test_authenticated_user_can_list_restaurants(): void
    {
        $restaurantId = $this->createRestaurant('Restaurante Listado');

        $this->authenticateAsAdmin($restaurantId);

        $response = $this->getJson('/api/restaurants');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Restaurante Listado',
        ]);
    }

    public function test_authenticated_user_can_get_restaurant(): void
    {
        $restaurantId = $this->createRestaurant('Restaurante Detalle');

        $this->authenticateAsAdmin($restaurantId);

        $restaurant = DB::table('restaurants')->where('id', $restaurantId)->first();

        $response = $this->getJson('/api/restaurants/' . $restaurant->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Restaurante Detalle',
            'email' => $restaurant->email,
        ]);
    }

    public function test_authenticated_user_can_update_restaurant(): void
    {
        $restaurantId = $this->createRestaurant('Restaurante Original');

        $this->authenticateAsAdmin($restaurantId);

        $restaurant = DB::table('restaurants')->where('id', $restaurantId)->first();
        $email = 'updated-restaurant-' . Str::uuid() . '@test.com';

        $response = $this->putJson('/api/restaurants/' . $restaurant->uuid, [
            'name' => 'Restaurante Actualizado',
            'legal_name' => 'Restaurante Actualizado SL',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => $email,
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Restaurante Actualizado',
            'email' => $email,
        ]);

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurantId,
            'name' => 'Restaurante Actualizado',
            'legal_name' => 'Restaurante Actualizado SL',
            'email' => $email,
        ]);
    }

    public function test_authenticated_user_can_change_restaurant_password(): void
    {
        $restaurantId = $this->createRestaurant('Restaurante Password');

        $this->authenticateAsAdmin($restaurantId);

        $restaurant = DB::table('restaurants')->where('id', $restaurantId)->first();

        $response = $this->patchJson('/api/restaurants/' . $restaurant->uuid . '/password', [
            'password' => 'new-password-123',
        ]);

        $response->assertStatus(200);

        $updatedRestaurant = DB::table('restaurants')->where('id', $restaurantId)->first();

        $this->assertTrue(Hash::check('new-password-123', $updatedRestaurant->password));
    }

    public function test_authenticated_user_can_delete_restaurant(): void
    {
        $restaurantId = $this->createRestaurant('Restaurante Para Borrar');

        $this->authenticateAsAdmin($restaurantId);

        $restaurant = DB::table('restaurants')->where('id', $restaurantId)->first();

        $response = $this->deleteJson('/api/restaurants/' . $restaurant->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('restaurants', [
            'id' => $restaurantId,
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
            'email' => 'admin-restaurant-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'pin' => '1234',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function createRestaurant(string $name = 'Restaurante Test'): int
    {
        return DB::table('restaurants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'legal_name' => $name . ' SL',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => 'restaurant-crud-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
