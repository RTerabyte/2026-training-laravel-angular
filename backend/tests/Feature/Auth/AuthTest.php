<?php

namespace Tests\Feature\Auth;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $restaurantId = $this->createRestaurant();
        $user = $this->createUser($restaurantId, 'admin', 'Admin Login');

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Admin Login',
            'email' => $user->email,
        ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $restaurantId = $this->createRestaurant();
        $user = $this->createUser($restaurantId, 'admin', 'Admin Login');

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout_with_bearer_token(): void
    {
        $restaurantId = $this->createRestaurant();
        $user = $this->createUser($restaurantId, 'admin', 'Admin Logout');
        $plainTextToken = $user->createToken('frontend')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $plainTextToken)
            ->postJson('/api/logout');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'message' => 'Logged out successfully',
        ]);

        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));
    }

    private function createUser(int $restaurantId, string $role, string $name): EloquentUser
    {
        return EloquentUser::create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'role' => $role,
            'image_src' => null,
            'name' => $name,
            'email' => 'auth-user-' . Str::uuid() . '@test.com',
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
            'email' => 'restaurant-auth-' . Str::uuid() . '@test.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
