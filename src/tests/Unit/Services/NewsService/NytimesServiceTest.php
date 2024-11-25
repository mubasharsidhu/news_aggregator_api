<?php

namespace Tests\Unit\Services\NewsService;

use Tests\TestCase;

use App\DTOs\ArticleDTO;
use App\Services\NewsService\NytimesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;

class NytimesServiceTest extends TestCase
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
        $this->service = new NytimesService('nytimes');
    }

    /**
     * Test successful fetching of articles
     *
     * @return void
     */
    public function testNytimesFetchArticlesSuccessfully(): void
    {
        Http::fake([
            'https://api.nytimes.com/svc/search/v2/articlesearch.json*' => Http::response([
                'response' => [
                    'docs' => [
                        [
                            'headline'       => ['main' => 'Test Article'],
                            'lead_paragraph' => 'This is a test',
                            'abstract'       => 'Content of the test article',
                            'source'          => 'Test Source',
                            'byline'         => ['original' => 'By John Doe'],
                            'multimedia'      => [
                                ['url' => 'https://example.com/image.jpg'],
                            ],
                            'web_url' => 'https://example.com/article',
                            'pub_date' => '2024-03-16T14:30:00-0500',
                        ],
                    ],
                    'meta' => [
                        'hits'   => 10,
                        'offset' => 0,
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
        $this->assertEquals(1, $response['totalPages']);
    }

    /**
     * Test fetching articles with an invalid source
     *
     * @return void
     */
    public function testNytimesFetchArticlesWithInvalidSource(): void
    {
        $service = new NytimesService('invalid-source');
        $this->expectException(\Exception::class);
        $service->fetchArticles(1, '2024-03-15');
    }

    /**
     * Test handling of failed HTTP request
     *
     * @return void
     */
    public function testNytimesHandleFailedHttpRequest(): void
    {
        Http::fake(['https://api.nytimes.com/svc/search/v2/articlesearch.json*' => Http::response('Failed', 500)]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed to fetch articles from nytimes: Failed");

        $this->service->fetchArticles(1, '2024-03-15');
    }

    /**
     * Test handling of empty or missing articles in response
     *
     * @return void
     */
    public function testNytimesHandleEmptyOrMissingArticles(): void
    {
        Http::fake([
            'https://api.nytimes.com/svc/search/v2/articlesearch.json*' => Http::response([
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
    public function testNytimesNormalizeArticleData(): void
    {
        $article = [
            'headline'       => ['main' => 'Test Title'],
            'lead_paragraph' => 'Test Description',
            'abstract'       => 'Test Content',
            'source'         => 'Test Source',
            'byline'         => ['original' => 'Test Author'],
            'multimedia'     => [
                ['url' => 'https://example.com/image.jpg'],
            ],
            'web_url'  => 'https://example.com/article',
            'pub_date' => '2024-03-16T14:30:00-0500',
        ];

        $normalizedArticle = $this->service->normalizeData($article);

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
