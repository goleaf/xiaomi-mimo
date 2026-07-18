<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register via API', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'device_name' => 'test-device',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('user can login via API', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
        'device_name' => 'test-device',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

test('user gets 401 with wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable();
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout');

    $response->assertNoContent();
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
});

test('unauthenticated user gets 401', function () {
    $response = $this->getJson('/api/user');
    $response->assertUnauthorized();
});
