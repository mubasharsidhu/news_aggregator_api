<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Jobs\FetchArticlesJob;
use App\Services\LoggerService;
use App\Services\NewsServiceFactory;
use App\Services\NewsService\NewsapiService;
use Illuminate\Support\Facades\Bus;
use App\Console\Commands\FetchArticles;
use Mockery;

class FetchArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function testFetchArticlesSourceNotFound(): void
    {
        $logger = Mockery::mock(LoggerService::class);
        $logger->shouldReceive('error')->once()->with('The --source option is required.');
        $this->app->instance(LoggerService::class, $logger);

        $this->artisan('articles:fetch')->assertFailed();
        $this->assertDatabaseCount('articles', 0);
    }

    public function testFetchArticlesInvalidSource(): void
    {
        $logger = Mockery::mock(LoggerService::class);
        $logger->shouldReceive('error')->once()->with('Invalid source: The --source can only contain one of these values: newsapi, guardian, nytimes');
        $this->app->instance(LoggerService::class, $logger);

        $this->artisan('articles:fetch', ['--source' => 'invalid_source'])->assertFailed();
        $this->assertDatabaseCount('articles', 0);
    }

    public function testFetchArticlesHandlesEmptyResponse(): void
    {
        $mockNewsService    = Mockery::mock(NewsServiceFactory::class);
        $mockNewsapiService = Mockery::mock(NewsapiService::class);

        // Mock fetchArticles to return no articles
        $mockNewsService->shouldReceive('create')->with('newsapi')->andReturn($mockNewsapiService);
        $mockNewsapiService->shouldReceive('fetchArticles')
            ->once()
            ->with(1, now()->subDay()->toDateString())
            ->andReturn([
                'currentPage'        => 1,
                'totalPages'         => 1,
                'normalizedArticles' => [],
            ]);

        $this->app->instance(NewsServiceFactory::class, $mockNewsService);

        $logger = Mockery::mock(LoggerService::class);
        $logger->shouldReceive('info')->once()->with('Fetching articles from newsapi...');
        $logger->shouldReceive('info')->once()->with('No articles found on page 1. Fetching completed.');
        $logger->shouldReceive('error')->never();
        $this->app->instance(LoggerService::class, $logger);

        $this->artisan('articles:fetch', ['--source' => 'newsapi'])->assertSuccessful();
        $this->assertDatabaseCount('articles', 0);
    }


    /**
     * Test the success scenario for FetchArticles command.
     *
     * @return void
     */
    public function testFetchArticlesSuccess(): void
    {
        // Mock the NewsServiceFactory and NewsapiService
        $mockNewsService    = Mockery::mock(NewsServiceFactory::class);
        $mockNewsapiService = Mockery::mock(NewsapiService::class);

        // Define the expected article data
        $articlesData = [
            'currentPage'        => 1,
            'totalPages'         => 1,
            'normalizedArticles' => [
                (object)[
                    'title'       => 'Test Article 1',
                    'description' => 'Description of test article 1',
                    'content'     => 'Content of test article 1',
                    'source'      => 'Source Name',
                    'author'      => 'Author Name',
                    'imageUrl'    => 'http://example.com/image.jpg',
                    'articleUrl'  => 'http://example.com/article-1',
                    'publishedAt' => '2024-11-24 00:00:00',
                    'apiSource'   => 'newsapi',
                ],
            ],
        ];

        // Configure mocks
        $mockNewsService->shouldReceive('create')
            ->with('newsapi')
            ->andReturn($mockNewsapiService);

        $mockNewsapiService->shouldReceive('fetchArticles')
            ->once()
            ->with(1, now()->subDay()->toDateString())
            ->andReturn($articlesData);

        $this->app->instance(NewsServiceFactory::class, $mockNewsService);

        $logger = Mockery::mock(LoggerService::class);
        $logger->shouldReceive('info')->once()->with('Fetching articles from newsapi...');
        $logger->shouldReceive('info')->once()->with('Processing articles...');
        $logger->shouldReceive('info')->once()->with('Page 1 has been processed. Articles have been saved successfully.');
        $logger->shouldReceive('info')->once()->with('All done for today! Fetching completed for newsapi.');
        $logger->shouldReceive('error')->never();

        $this->app->instance(LoggerService::class, $logger);

        $this->artisan('articles:fetch', ['--source' => 'newsapi','--page' => 1,])->assertSuccessful();
        $this->assertDatabaseHas('articles', [
            'title'       => 'Test Article 1',
            'description' => 'Description of test article 1',
            'content'     => 'Content of test article 1',
            'source'      => 'Source Name',
            'author'      => 'Author Name',
            'imageUrl'    => 'http://example.com/image.jpg',
            'articleUrl'  => 'http://example.com/article-1',
            'publishedAt' => '2024-11-24 00:00:00',
            'apiSource'   => 'newsapi',
        ]);
    }

    /**
     * Test if the FetchArticlesJob is dispatched when multiple pages are fetched.
     */
    public function testFetchArticlesSuccessDispatchesForNextPage()
    {
        // Mock the NewsServiceFactory and NewsapiService
        $mockNewsService    = Mockery::mock(NewsServiceFactory::class);
        $mockNewsapiService = Mockery::mock(NewsapiService::class);

        // Define the expected article data
        $articlesData = [
            'currentPage'        => 1,
            'totalPages'         => 2,
            'normalizedArticles' => [
                (object)[
                    'title'       => 'Test Article 1',
                    'description' => 'Description of test article 1',
                    'content'     => 'Content of test article 1',
                    'source'      => 'Source Name',
                    'author'      => 'Author Name',
                    'imageUrl'    => 'http://example.com/image.jpg',
                    'articleUrl'  => 'http://example.com/article-1',
                    'publishedAt' => now()->subDay()->toDateString(),
                    'apiSource'   => 'newsapi',
                    'created_at'  => now()->subDay()->toDateString(),
                    'updated_at'  => now()->subDay()->toDateString(),
                ],
            ],
        ];

        // Configure mocks
        $mockNewsService->shouldReceive('create')
            ->with('newsapi')
            ->andReturn($mockNewsapiService);

        $mockNewsapiService->shouldReceive('fetchArticles')
            ->once()
            ->with(1, now()->subDay()->toDateString())
            ->andReturn($articlesData);

        $this->app->instance(NewsServiceFactory::class, $mockNewsService);

        $logger = Mockery::mock(LoggerService::class);
        $logger->shouldReceive('info')->once()->with('Fetching articles from newsapi...');
        $logger->shouldReceive('info')->once()->with('Processing articles...');
        $logger->shouldReceive('info')->once()->with('Page 1 has been processed. Articles have been saved successfully.');
        $logger->shouldReceive('info')->once()->with('Fetching next page...');
        $logger->shouldReceive('info')->once()->with('Next page:2 is dispatched and will be executed in 12 seconds.');
        $logger->shouldReceive('error')->never();

        $this->app->instance(LoggerService::class, $logger);

        // Mock the job dispatch
        Bus::fake();

        $this->artisan(
            'articles:fetch',
            ['--source' => 'newsapi','--page' => 1, '--from'=> now()->subDay()->toDateString()]
        )->assertSuccessful();

        // Assert the FetchArticlesJob is dispatched for the next page
        Bus::assertDispatched(FetchArticlesJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $source     = $reflection->getProperty('source');
            $source->setAccessible(true);
            $page = $reflection->getProperty('page');
            $page->setAccessible(true);
            $from = $reflection->getProperty('from');
            $from->setAccessible(true);

            return $source->getValue($job) === 'newsapi'
                && $page->getValue($job) === 2
                && $from->getValue($job) === now()->subDay()->toDateString();
        });
    }

    public function testFetchArticlesHandlesServiceException(): void
    {
        $mockNewsService    = Mockery::mock(NewsServiceFactory::class);
        $mockNewsapiService = Mockery::mock(NewsapiService::class);

        $mockNewsService->shouldReceive('create')->with('newsapi')->andReturn($mockNewsapiService);
        $mockNewsapiService->shouldReceive('fetchArticles')
            ->once()
            ->with(1, now()->subDay()->toDateString())
            ->andThrow(new \Exception('Failed to fetch articles.'));

        $this->app->instance(NewsServiceFactory::class, $mockNewsService);

        $logger = Mockery::mock(LoggerService::class);
        $logger->shouldReceive('info')->once()->with('Fetching articles from newsapi...');
        $logger->shouldReceive('error')->once()->with('Error fetching articles: Failed to fetch articles.');
        $this->app->instance(LoggerService::class, $logger);

        $this->artisan('articles:fetch', ['--source' => 'newsapi'])
            ->expectsOutput('Error state.')
            ->assertFailed();

        // Ensure no articles are added to the database
        $this->assertDatabaseCount('articles', 0);
    }

    public function testValidatesArticleSuccessfully()
    {
        $article = [
            'title'       => 'Test Article Title',
            'description' => 'A sample description.',
            'content'     => 'Content goes here.',
            'source'      => 'Test Source',
            'author'      => 'Test Author',
            'imageUrl'    => 'http://example.com/image.jpg',
            'articleUrl'  => 'http://example.com/article',
            'publishedAt' => '2024-11-20',
            'apiSource'   => 'Test API',
        ];

        $fetchArticles = new FetchArticles(app(NewsServiceFactory::class));
        $validator     = $fetchArticles->validateArticle($article);
        $this->assertFalse($validator->fails(), 'Article validation should pass');
    }


    public function testFailsToValidateMissingRequiredFields()
    {
        $article = [
            // Missing 'title' and 'articleUrl'
            'description' => 'A sample description.',
            'content'     => 'Content goes here.',
            'source'      => 'Test Source',
            'publishedAt' => '2024-11-20',
            'apiSource'   => 'Test API',
        ];

        $fetchArticles = new FetchArticles(app(NewsServiceFactory::class));
        $validator     = $fetchArticles->validateArticle($article);

        $this->assertTrue($validator->fails(), 'Validation should fail due to missing required fields');
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('articleUrl', $validator->errors()->toArray());
    }

    public function testFailsToValidateInvalidArticleUrl()
    {
        $article = [
            'title'       => 'Test Article Title',
            'description' => 'A sample description.',
            'content'     => 'Content goes here.',
            'source'      => 'Test Source',
            'author'      => 'Test Author',
            'imageUrl'    => 'http://example.com/image.jpg',
            'articleUrl'  => 'invalid-url', // Invalid URL
            'publishedAt' => '2024-11-20',
            'apiSource'   => 'Test API',
        ];

        $fetchArticles = new FetchArticles(app(NewsServiceFactory::class));
        $validator     = $fetchArticles->validateArticle($article);

        $this->assertTrue($validator->fails(), 'Validation should fail due to invalid articleUrl');
        $this->assertArrayHasKey('articleUrl', $validator->errors()->toArray());
    }

    public function testFailsToValidateExceedingMaxLengthForStringFields()
    {
        $article = [
            'title'       => str_repeat('A', 256), // Exceeds max length
            'source'      => str_repeat('C', 256), // Exceeds max length
            'author'      => str_repeat('D', 256), // Exceeds max length
            'apiSource'   => str_repeat('E', 256), // Exceeds max length
            'imageUrl'    => 'http://example.com/image.jpg',
            'articleUrl'  => 'http://example.com/article',
            'publishedAt' => '2024-11-20',
        ];

        $fetchArticles = new FetchArticles(app(NewsServiceFactory::class));
        $validator     = $fetchArticles->validateArticle($article);

        $this->assertTrue($validator->fails(), 'Validation should fail due to exceeding max length');
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('source', $validator->errors()->toArray());
        $this->assertArrayHasKey('author', $validator->errors()->toArray());
        $this->assertArrayHasKey('apiSource', $validator->errors()->toArray());
    }

    public function testFailsToValidateInvalidDateFormatForPublishedAt()
    {
        $article = [
            'title'       => 'Test Article Title',
            'description' => 'A sample description.',
            'content'     => 'Content goes here.',
            'source'      => 'Test Source',
            'author'      => 'Test Author',
            'imageUrl'    => 'http://example.com/image.jpg',
            'articleUrl'  => 'http://example.com/article',
            'publishedAt' => 'invalid-date', // Invalid date
            'apiSource'   => 'Test API',
        ];

        $fetchArticles = new FetchArticles(app(NewsServiceFactory::class));
        $validator     = $fetchArticles->validateArticle($article);

        $this->assertTrue($validator->fails(), 'Validation should fail due to invalid date format for publishedAt');
        $this->assertArrayHasKey('publishedAt', $validator->errors()->toArray());
    }
}