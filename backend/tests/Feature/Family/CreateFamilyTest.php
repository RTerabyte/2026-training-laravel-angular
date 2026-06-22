<?php

namespace Tests\Feature\Family;

use Tests\TestCase;

class CreateFamilyTest extends TestCase
{
    public function test_post_families_requires_authentication(): void
    {
        $response = $this->postJson('/api/families', [
            'restaurant_id' => 1,
            'name' => 'Entrantes',
            'active' => true,
        ]);

        $response->assertStatus(401);
    }
    
}
