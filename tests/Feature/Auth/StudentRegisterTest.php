<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Students\Models\Student;
use Tests\TestCase;

class StudentRegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $registerUrl = '/api/v1/auth/register';
    private string $loginUrl = '/api/v1/auth/login';

    public function test_student_register_requires_fields()
    {
        $response = $this->postJson($this->registerUrl, []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_student_register_invalid_email()
    {
        $response = $this->postJson($this->registerUrl, [
            'name' => 'Student',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_student_register_password_too_short()
    {
        $response = $this->postJson($this->registerUrl, [
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_student_register_password_mismatch()
    {
        $response = $this->postJson($this->registerUrl, [
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_student_register_email_already_exists()
    {
        Student::create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson($this->registerUrl, [
            'name' => 'New Student',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_student_register_success()
    {
        $response = $this->postJson($this->registerUrl, [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'data' => [
                         'token',
                         'student' => ['id', 'name', 'email']
                     ]
                 ]);

        $this->assertDatabaseHas('students', [
            'email' => 'newstudent@example.com'
        ]);
        
        $this->assertDatabaseHas('student_email_verifications', [
            'email' => 'newstudent@example.com'
        ]);
    }
    
    public function test_student_login_wrong_credentials()
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'notfound@example.com',
            'password' => 'wrongpass'
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('success', false)
                 ->assertJsonFragment(['message' => 'Email hoặc mật khẩu không đúng.']);
    }
    
    public function test_student_login_unverified_email()
    {
        $student = Student::create([
            'name' => 'Student',
            'email' => 'unverified@example.com',
            'password' => 'password123',
            'email_verified_at' => null, 
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'unverified@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('success', false)
                 ->assertJsonFragment(['message' => 'Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để xác thực tài khoản.']);
    }
    
    public function test_student_login_success()
    {
        $student = Student::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password123',
            'email_verified_at' => now(), 
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'student@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'data' => [
                         'token',
                         'student' => ['id', 'name', 'email']
                     ]
                 ]);
    }

    public function test_student_logout_success()
    {
        $student = Student::create([
            'name' => 'Student Logout',
            'email' => 'logout_student@example.com',
            'password' => 'password123',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($student, 'api');

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }
}
