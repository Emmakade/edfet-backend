<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'password' => 'password'
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'teacher@example.com']);

        $login = $this->postJson('/api/login', [
            'email' => 'teacher@example.com',
            'password' => 'password'
        ])->assertStatus(200);

        $this->assertArrayHasKey('token', $login->json());
    }

    public function test_super_admin_can_access_protected_route()
    {
        $user = User::factory()->create()->assignRole('super-admin');
        $token = $user->createToken('api')->plainTextToken;

        $this->getJson('/api/classes', [
            'Authorization' => 'Bearer ' . $token
        ])->assertStatus(200);
    }
}
