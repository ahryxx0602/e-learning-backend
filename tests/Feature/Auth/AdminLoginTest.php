<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Models\User;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/api/v1/admin/auth/login';

    public function test_admin_login_requires_email_and_password()
    {
        $response = $this->postJson($this->loginUrl, []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_admin_login_invalid_email_format()
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_login_short_password()
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'admin@example.com',
            'password' => '12345'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_login_wrong_credentials()
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'notfound@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Email hoặc mật khẩu không đúng.']);
    }

    public function test_admin_login_wrong_password()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'correctpassword',
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJsonFragment(['message' => 'Email hoặc mật khẩu không đúng.']);
    }

    public function test_admin_login_success()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'admin@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'data' => [
                         'token',
                         'user' => ['id', 'name', 'email']
                     ]
                 ]);
    }

    public function test_admin_logout_success()
    {
        $admin = User::create([
            'name' => 'Admin Logout',
            'email' => 'logout@example.com',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->postJson('/api/v1/admin/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }
}
