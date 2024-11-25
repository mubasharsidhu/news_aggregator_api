<?php

namespace Tests\Feature\Controller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserPreferencesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRetrievesUserPreferences()
    {
        $user = User::factory()->create([
            'preferred_sources' => ['Source 1', 'Source 2'],
            'preferred_authors' => ['Author 1', 'Author 2'],
        ]);

        $response = $this->actingAs($user)->getJson('/api/user/preferences');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User preferences retrieved successfully.',
                'data' => [
                    'preferred_sources' => ['Source 1', 'Source 2'],
                    'preferred_authors' => ['Author 1', 'Author 2'],
                ],
            ]);
    }


    public function testUpdatesUserPreferences()
    {
        $user    = User::factory()->create();
        $payload = [
            'preferred_sources' => ['New Source 1', 'New Source 2'],
            'preferred_authors' => ['New Author 1', 'New Author 2'],
        ];

        $response = $this->actingAs($user)->putJson('/api/user/preferences', $payload);
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Preferences updated successfully.',
                'data'    => $payload,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        $userFromDatabase = DB::table('users')->find($user->id);

        $this->assertEquals(
            $payload['preferred_sources'],
            json_decode($userFromDatabase->preferred_sources, true)
        );

        $this->assertEquals(
            $payload['preferred_authors'],
            json_decode($userFromDatabase->preferred_authors, true)
        );


    }

    public function testReturnsValidationErrorsWhenUpdatingWithInvalidData()
    {
        $user    = User::factory()->create();
        $payload = [
            'preferred_sources' => 'invalid data',
            'preferred_authors' => 'invalid data',
        ];

        $response = $this->actingAs($user)->putJson('/api/user/preferences', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'preferred_sources' => ['The preferred sources field must be an array.'],
                    'preferred_authors' => ['The preferred authors field must be an array.'],
                ],
            ]);
    }

    public function testEnsuresUserIsAuthenticatedToAccessPreferences()
    {
        $response = $this->getJson('/api/user/preferences');
        $response->assertStatus(401);
    }


    public function testEnsuresUserIsAuthenticatedToUpdatePreferences()
    {
        $payload = [
            'preferred_sources' => ['Some Source'],
            'preferred_authors' => ['Some Author'],
        ];
        $response = $this->putJson('/api/user/preferences', $payload);
        $response->assertStatus(401);
    }

}
