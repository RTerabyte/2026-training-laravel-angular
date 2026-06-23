<?php

namespace Tests\Feature\User;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_users_requires_authentication(): void
    {
        $response = $this->postJson('/api/users', [
            'restaurant_id' => 1,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'Usuario Test',
            'email' => 'usuario@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'pin' => '1234',
        ]);

        $response->assertStatus(401);
    }

    public function test_operator_cannot_create_user(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsOperator($restaurantId);

        $response = $this->postJson('/api/users', [
            'restaurant_id' => $restaurantId,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'Usuario No Permitido',
            'email' => 'operator-forbidden-' . Str::uuid() . '@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'pin' => '1234',
        ]);

        $response->assertStatus(403);

        $response->assertJsonFragment([
            'message' => 'Forbidden',
        ]);
    }

    public function test_admin_can_create_user(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $email = 'created-user-' . Str::uuid() . '@test.com';

        $response = $this->postJson('/api/users', [
            'restaurant_id' => $restaurantId,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'Usuario Creado',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'pin' => '1234',
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'name' => 'Usuario Creado',
            'email' => $email,
        ]);

        $this->assertDatabaseHas('users', [
            'restaurant_id' => $restaurantId,
            'role' => 'operator',
            'name' => 'Usuario Creado',
            'email' => $email,
        ]);
    }

    public function test_authenticated_user_can_list_users(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $user = $this->createUser($restaurantId, 'operator', 'Usuario Listado');

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_authenticated_user_can_get_user(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $user = $this->createUser($restaurantId, 'operator', 'Usuario Detalle');

        $response = $this->getJson('/api/users/' . $user->uuid);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Usuario Detalle',
            'email' => $user->email,
        ]);
    }

    public function test_authenticated_user_can_update_user(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $user = $this->createUser($restaurantId, 'operator', 'Usuario Original');

        $response = $this->putJson('/api/users/' . $user->uuid, [
            'name' => 'Usuario Actualizado',
            'role' => 'admin',
            'pin' => '4321',
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Usuario Actualizado',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'restaurant_id' => $restaurantId,
            'name' => 'Usuario Actualizado',
            'role' => 'admin',
            'pin' => '4321',
        ]);
    }

    public function test_authenticated_user_can_delete_user(): void
    {
        $restaurantId = $this->createRestaurant();

        $this->authenticateAsAdmin($restaurantId);

        $user = $this->createUser($restaurantId, 'operator', 'Usuario Para Borrar');

        $response = $this->deleteJson('/api/users/' . $user->uuid);

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_get_me(): void
    {
        $restaurantId = $this->createRestaurant();

        $admin = $this->authenticateAsAdmin($restaurantId);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => $admin->name,
            'email' => $admin->email,
        ]);
    }

    private function authenticateAsAdmin(int $restaurantId): EloquentUser
    {
        $user = $this->createUser($restaurantId, 'admin', 'Admin Test');

        Sanctum::actingAs($user);

        return $user;
    }

    private function authenticateAsOperator(int $restaurantId): EloquentUser
    {
        $user = $this->createUser($restaurantId, 'operator', 'Operator Test');

        Sanctum::actingAs($user);

        return $user;
    }

    private function createUser(int $restaurantId, string $role, string $name): EloquentUser
    {
        return EloquentUser::create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => $role,
            'image_src' => null,
            'name' => $name,
            'email' => 'user-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'pin' => '1234',
        ]);
    }

    private function createRestaurant(): int
    {
        return DB::table('restaurants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurante Test',
            'legal_name' => 'Restaurante Test SL',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => 'restaurant-user-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}