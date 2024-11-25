<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

use App\Services\NewsServiceFactory;
use App\Services\NewsService\NewsApiService; // Example existing service
use Exception;

class NewsServiceFactoryTest extends TestCase
{
    /**
     * Test successful creation of a news service instance.
     *
     * @return void
     */
    public function testCreateNewsServiceInstanceSuccessfully(): void
    {
        $factory  = new NewsServiceFactory();
        $instance = $factory->create('newsapi');
        $this->assertInstanceOf(NewsApiService::class, $instance);
    }

    /**
     * Test creation with a non-existent service class throws an Exception.
     *
     * @return void
     */
    public function testCreateThrowsExceptionForNonExistentService(): void
    {
        $factory           = new NewsServiceFactory();
        $nonExistentSource = 'nonexistentapi';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("API Service class App\\Services\\NewsService\\". ucfirst($nonExistentSource). "Service not found.");
        $factory->create($nonExistentSource);
    }

    /**
     * Test passing null or empty string as the source throws an Exception.
     *
     * @return void
     */
    public function testCreateThrowsExceptionForEmptyOrNullSource(): void
    {
        $factory = new NewsServiceFactory();
        $this->expectException(Exception::class);
        $factory->create(null);

        $this->expectException(Exception::class);
        $factory->create('');
    }
}
