<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test mass assignment protection for the User model.
     *
     * @return void
     */
    public function testUserFillableAttributes()
    {
        $fillableAttributes = (new User())->getFillable();

        $this->assertEquals([
            'name',
            'email',
            'password',
            'preferred_sources',
            'preferred_authors',
        ], $fillableAttributes);
    }
}
