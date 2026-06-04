<?php

namespace Tests\Feature\User;

use Tests\TestCase;

class CreateUserTest extends TestCase
{
    public function test_post_users_requires_authentication(): void
    {
        $response = $this->postJson('/api/users', [
            'restaurant_id' => 1,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'Integration User',
            'email' => 'integration@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'pin' => '1234',
        ]);

        $response->assertStatus(401);
    }
}