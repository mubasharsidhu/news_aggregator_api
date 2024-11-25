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

    /**
     * Test if the user preferences are successfully retrieved.
     *
     * @return void
     * */
    public function testRetrievesUserPreferences(): void
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

    /**
     * Test if the user preferences are successfully updated.
     *
     * @return void
     * */
    public function testUpdatesUserPreferences(): void
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

    /**
     * Test if validation errors are returned when invalid data is provided while updating user preferences.
     *
     * @return void
     */
    public function testReturnsValidationErrorsWhenUpdatingWithInvalidData(): void
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

    /**
     * Test if an unauthenticated user is prevented from accessing user preferences.
     *
     * @return void
     */
    public function testEnsuresUserIsAuthenticatedToAccessPreferences(): void
    {
        $response = $this->getJson('/api/user/preferences');
        $response->assertStatus(401);
    }

    /**
     * Test if an unauthenticated user is prevented from updating user preferences.
     *
     * @return void
     */
    public function testEnsuresUserIsAuthenticatedToUpdatePreferences(): void
    {
        $payload = [
            'preferred_sources' => ['Some Source'],
            'preferred_authors' => ['Some Author'],
        ];
        $response = $this->putJson('/api/user/preferences', $payload);
        $response->assertStatus(401);
    }

}
