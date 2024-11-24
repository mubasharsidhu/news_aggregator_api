<?php

namespace Tests\Feature\Controller;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistrationFailsWithInvalidData()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => '',
            'email'                 => 'invalid-email',
            'password'              => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function testUserRegistration()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'SecureP@ssw0rd123',
            'password_confirmation' => 'SecureP@ssw0rd123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function testUserCanLoginWithValidCredentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('token', $responseData['data']);
        $this->assertNotEmpty($responseData['data']['token']);
    }

    public function testLoginFailsWithInvalidCredentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }

    public function testLogoutSuccess()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name'         => 'TestToken',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Test logout when no user is authenticated.
     *
     * @return void
     */
    public function testLogoutNotAuthenticatedUser()
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function testSendsResetLinkForValidEmail()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link sent.',
            ]);
    }

    public function testReturnsValidationErrorForInvalidEmail()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ]);
    }

    public function testReturnsErrorWhenResetLinkCannotBeSent()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
            ->andReturn(Password::INVALID_USER);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to send reset link.',
            ]);
    }

    public function testResetsThePasswordSuccessfully()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token    = Password::createToken($user);
        $response = $this->postJson('/api/reset-password/' . $token, [
            'email'                 => $user->email,
            'password'              => 'SecureP@ssw0rd123',
            'password_confirmation' => 'SecureP@ssw0rd123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successful.',
            ]);

        // Check if the password was updated in the database
        $this->assertTrue(Hash::check('SecureP@ssw0rd123', $user->fresh()->password));
    }

    public function testReturnsValidationErrorIfPasswordIsInvalid()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Generate a valid token
        $token = Password::createToken($user);

        // Make an invalid password reset request (password too short)
        $response = $this->postJson('/api/reset-password/' . $token, [
            'email'                 => $user->email,
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ]);

        // Ensure the password was not updated
        $this->assertFalse(Hash::check('short', $user->fresh()->password));
    }


    public function testReturnsErrorIfTokenIsInvalid()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $invalidToken = 'invalid-token';
        $response     = $this->postJson('/api/reset-password/' . $invalidToken, [
            'email'                 => $user->email,
            'password'              => 'SecureP@ssw0rd123',
            'password_confirmation' => 'SecureP@ssw0rd123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided token is invalid.',
            ]);
    }


    public function testReturnsErrorIfPasswordsDoNotMatch()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token    = Password::createToken($user);
        $response = $this->postJson('/api/reset-password/' . $token, [
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ]);
    }


    public function testReturnsErrorIfEmailIsMissing()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token    = Password::createToken($user);
        $response = $this->postJson('/api/reset-password/' . $token, [
            'password'              => 'SecureP@ssw0rd123',
            'password_confirmation' => 'SecureP@ssw0rd123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided token is invalid.',
            ]);
    }

}
