<?php

namespace Tests\Unit\Services\NewsService;

use Tests\TestCase;

use App\Services\NewsService\NewsapiService;
use App\DTOs\ArticleDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;

class NewsapiServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $service;

    /**
     * Set up method for initializing the GuardianService instance
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NewsapiService('newsapi');
    }

    /**
     * Test successful fetching of articles
     *
     * @return void
     */
    public function testNewsapiFetchArticlesSuccessfully(): void
    {
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response([
                'status'        => 'ok',
                'totalResults' => 100,
                'articles'     => [
                    [
                        'title'       => 'Test Article',
                        'description' => 'This is a test',
                        'content'     => 'Content of the test article',
                        'source'      => ['name' => 'Test Source'],
                        'author'      => 'John Doe',
                        'urlToImage'  => 'https://example.com/image.jpg',
                        'url'         => 'https://example.com/article',
                        'publishedAt' => '2024-03-16T14:30:00Z',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->service->fetchArticles(1, '2024-03-15');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('currentPage', $response);
        $this->assertArrayHasKey('totalPages', $response);
        $this->assertArrayHasKey('normalizedArticles', $response);
        $this->assertCount(1, $response['normalizedArticles']);
        $this->assertInstanceOf(ArticleDTO::class, $response['normalizedArticles'][0]);
        $this->assertEquals('Test Article', $response['normalizedArticles'][0]->title);
        $this->assertEquals(2, $response['totalPages']);
    }

    /**
     * Test to skip an article if it does not contain title
     *
     * @return void
     */
    public function testNewsapiFetchArticlesSkipsRemovedTitles(): void
    {
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response([
                'status'       => 'ok',
                'totalResults' => 100,
                'articles'     => [
                    [
                        'title'       => '[Removed]',
                        'description' => '',
                        'content'     => '',
                        'source'      => ['name' => ''],
                        'author'      => '',
                        'urlToImage'  => '',
                        'url'         => '',
                        'publishedAt' => '',
                    ],
                    [
                        'title'       => 'Test Article',
                        'description' => 'This is a test',
                        'content'     => 'Content of the test article',
                        'source'      => ['name' => 'Test Source'],
                        'author'      => 'John Doe',
                        'urlToImage'  => 'https://example.com/image.jpg',
                        'url'         => 'https://example.com/article',
                        'publishedAt' => '2024-03-16T14:30:00Z',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->service->fetchArticles(1, '2024-03-15');

        // Assert the '[Removed]' article is not in the result
        $this->assertCount(1, $response['normalizedArticles']);
        $this->assertNotEquals('[Removed]', $response['normalizedArticles'][0]->title);
    }

    /**
     * Test fetching articles with an invalid source
     *
     * @return void
     */
    public function testNewsapiFetchArticlesWithInvalidSource(): void
    {
        $service = new NewsapiService('invalid-source');
        $this->expectException(\Exception::class);
        $service->fetchArticles(1, '2024-03-15');
    }

    /**
     * Test handling of failed HTTP request
     *
     * @return void
     */
    public function testNewsapiHandleFailedHttpRequest(): void
    {
        Http::fake(['https://newsapi.org/v2/everything*' => Http::response('Failed', 500)]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed to fetch articles from newsapi: Failed");

        $this->service->fetchArticles(1, '2024-03-15');
    }

    /**
     * Test handling of empty or missing articles in response
     *
     * @return void
     */
    public function testNewsapiHandleEmptyOrMissingArticles(): void
    {
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response([
                'status'       => 'ok',
                'totalResults' => 100,
            ], 200),
        ]);

        $response = $this->service->fetchArticles(1, '2024-03-15');

        // Assertions
        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('currentPage', $response);
        $this->assertArrayNotHasKey('totalPages', $response);
        $this->assertArrayNotHasKey('normalizedArticles', $response);
    }

    /**
     * Test normalization of article data
     *
     * @return void
     */
    public function testNewsapiNormalizeArticleData(): void
    {
        $article = [
            'title'       => 'Test Title',
            'description' => 'Test Description',
            'content'     => 'Test Content',
            'source'      => ['name' => 'Test Source'],
            'author'      => 'Test Author',
            'urlToImage'  => 'https://example.com/image.jpg',
            'url'         => 'https://example.com/article',
            'publishedAt' => '2024-03-16T14:30:00Z',
        ];

        $normalizedArticle = $this->service->normalizeData($article);

        // Assertions
        $this->assertInstanceOf(ArticleDTO::class, $normalizedArticle);

        $this->assertEquals('Test Title', $normalizedArticle->title);
        $this->assertEquals('Test Description', $normalizedArticle->description);
        $this->assertEquals('Test Content', $normalizedArticle->content);
        $this->assertEquals('Test Source', $normalizedArticle->source);
        $this->assertEquals('Test Author', $normalizedArticle->author);
        $this->assertEquals('https://example.com/image.jpg', $normalizedArticle->imageUrl);
        $this->assertEquals('https://example.com/article', $normalizedArticle->articleUrl);
        $this->assertInstanceOf(Carbon::class, Carbon::parse($normalizedArticle->publishedAt));
    }
}
