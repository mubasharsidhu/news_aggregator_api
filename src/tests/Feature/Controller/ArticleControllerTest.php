<?php

namespace Tests\Feature\Controller;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Route;
use App\Models\Article;
use App\Models\User;
use App\Http\Controllers\ArticleController;

class ArticleControllerTest extends TestCase
{
    // Trait to refresh the database after each test
    use RefreshDatabase;

    protected $user;
    protected $withHeaders;

    /**
     * Set up method to create a user and generate authorization token
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'preferred_sources' => ['Tech News'],
            'preferred_authors' => ['John Doe'],
        ]);

        // Creating authorization headers for the user
        $this->withHeaders = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->createToken('TestToken')->plainTextToken,
        ]);
    }

    /**
     * Test the 'articles' method with various filters like sources and authors.
     *
     * @return void
     */
    public function testArticlesWithFilters(): void
    {
        Article::factory()->create(['source' => 'Tech News', 'author' => 'John Doe']);
        Article::factory()->create(['source' => 'Health News', 'author' => 'Jane Smith']);

        $response = $this->withHeaders->getJson('/api/articles/feeds', [
            'sources' => 'Tech News',
            'authors' => 'John Doe',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['source' => 'Tech News']);
        $response->assertJsonFragment(['source' => 'Health News']);
    }

    /**
     * Test the 'articles' method by filtering with a keyword.
     *
     * @return void
     */
    public function testArticlesFilterByKeyword(): void
    {
        $matchingArticle = Article::factory()->create([
            'title' => 'This is the matching test Article'
        ]);

        $nonMatchingArticle = Article::factory()->create([
            'title' => 'This is the non-matching'
        ]);

        $response = $this->getJson('/api/articles/feeds?keyword=Article');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['title' => $matchingArticle->title]);
        $response->assertJsonMissing(['title' => $nonMatchingArticle->title]);
    }

    /**
     * Test the 'articles' method by filtering with a date.
     */
    public function testArticlesFilterByDate(): void
    {
        $matchingArticle = Article::factory()->create([
            'publishedAt' => '2024-11-25',
        ]);
        $nonMatchingArticle = Article::factory()->create([
            'publishedAt' => '2024-11-24',
        ]);

        $response = $this->getJson('/api/articles/feeds?date=2024-11-25');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['publishedAt' => '2024-11-25 00:00:00']);
        $response->assertJsonMissing(['publishedAt' => '2024-11-24 00:00:00']);
    }

    /**
     * Test the 'articles' method for a personalized feed based on user's preferences.
     *
     * @return void
     */
    public function testArticlesPersonalizedFeed(): void
    {
        Article::factory()->create(['source' => 'Tech News', 'author' => 'John Doe']);
        Article::factory()->create(['source' => 'Health News', 'author' => 'Jane Smith']);

        $this->actingAs($this->user);

        Route::name('personalized.feed')->get('/feed', [ArticleController::class, 'articles']);

        $response = $this->withHeaders->getJson(route('personalized.feed'));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['source' => 'Tech News']);
        $response->assertJsonFragment(['author' => 'John Doe']);
    }

    /**
     * Test retrieving an article by its ID when the article exists.
     *
     * @return void
     */
    public function testArticleFound(): void
    {
        $article  = Article::factory()->create();
        $response = $this->withHeaders->getJson('/api/article/' . $article->id);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => $article->title]);
    }

    /**
     * Test retrieving an article by its ID when the article does not exist.
     *
     * @return void
     */
    public function testArticleNotFound()
    {
        $response = $this->withHeaders->getJson('/api/article/9999');
        $response->assertStatus(404);
        $response->assertJson(['success' => false, 'message' => 'Article not found.']);
    }

    /**
     * Test the 'uniqueSources' method to get distinct article sources.
     *
     * @return void
     */
    public function testUniqueSources(): void
    {
        Article::factory()->create(['source' => 'Tech News']);
        Article::factory()->create(['source' => 'Health News']);

        $response = $this->withHeaders->getJson('/api/articles/unique-sources');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['Tech News']);
        $response->assertJsonFragment(['Health News']);
    }

    /**
     * Test the 'uniqueAuthors' method to get distinct article authors.
     *
     * @return void
     */
    public function testUniqueAuthors()
    {
        Article::factory()->create(['author' => 'John Doe']);
        Article::factory()->create(['author' => 'Jane Smith']);

        $response = $this->withHeaders->getJson('api/articles/unique-authors');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['John Doe']);
        $response->assertJsonFragment(['Jane Smith']);
    }
}
