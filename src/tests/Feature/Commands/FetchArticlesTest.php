<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Jobs\FetchArticlesJob;
use App\Services\LoggerService;
use App\Services\NewsServiceFactory;
use App\Services\NewsService\NewsapiService;
use Illuminate\Support\Facades\Bus;
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


}
