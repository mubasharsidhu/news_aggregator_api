<?php

namespace Tests\Unit\Services\NewsService;

use Tests\TestCase;

use App\DTOs\ArticleDTO;
use App\Services\NewsService\GuardianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;

class GuardianServiceTest extends TestCase
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
        $this->service = new GuardianService('guardian');
    }

    /**
     * Test successful fetching of articles from the Guardian API
     *
     * @return void
     */
    public function testGuardianFetchArticlesSuccessfully(): void
    {
        Http::fake([
            'https://content.guardianapis.com/search*' => Http::response([
                'response' => [
                    'currentPage' => 1,
                    'pages'       => 2,
                    'results'     => [
                        [
                            'webTitle' => 'Test Article',
                            'fields'   => [
                                'standfirst'  => 'This is a test',
                                'body'        => 'Content of the test article',
                                'publication' => 'Test Source',
                                'byline'      => 'John Doe',
                                'thumbnail'   => 'https://example.com/image.jpg',
                            ],
                            'webUrl' => 'https://example.com/article',
                            'webPublicationDate' => '2024-03-16T14:30:00Z',
                        ],
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
     * Test fetching articles with an invalid source
     *
     * @return void
     */
    public function testGuardianFetchArticlesWithInvalidSource(): void
    {
        $service = new GuardianService('invalid-source');
        $this->expectException(\Exception::class);
        $service->fetchArticles(1, '2024-03-15');
    }

    /**
     * Test handling of failed HTTP request
     *
     * @return void
     */
    public function testGuardianHandleFailedHttpRequest(): void
    {
        Http::fake(['https://content.guardianapis.com/search*' => Http::response('Failed', 500)]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed to fetch articles from guardian: Failed");

        $this->service->fetchArticles(1, '2024-03-15');
    }

    /**
     * Test handling of empty or missing articles in response
     *
     * @return void
     */
    public function testGuardianHandleEmptyOrMissingArticles(): void
    {
        Http::fake([
            'https://content.guardianapis.com/search*' => Http::response([
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
    public function testGuardianNormalizeArticleData(): void
    {
        $article = [
            'webTitle' => 'Test Title',
            'fields'   => [
                'standfirst'  => 'Test Description',
                'body'        => 'Test Content',
                'publication' => 'Test Source',
                'byline'      => 'Test Author',
                'thumbnail'   => 'https://example.com/image.jpg',
            ],
            'webUrl'             => 'https://example.com/article',
            'webPublicationDate' => '2024-03-16T14:30:00Z',
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
