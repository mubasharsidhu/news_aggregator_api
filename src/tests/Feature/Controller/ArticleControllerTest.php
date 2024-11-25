<?php

namespace Tests\Feature\Controller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $withHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user              = User::factory()->create([
            'preferred_sources' => ['Tech News'],
            'preferred_authors' => ['John Doe'],
        ]);
        $this->withHeaders = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->createToken('TestToken')->plainTextToken,
        ]);
    }

    /**
     * Test the 'articles' method with various filters.
     */
    public function testArticlesWithFilters()
    {
        Article::factory()->create(['source' => 'Tech News', 'author' => 'John Doe']);
        Article::factory()->create(['source' => 'Health News', 'author' => 'Jane Smith']);

        $response = $this->withHeaders->getJson('/api/articles/feeds', [
            'sources' => 'Tech News',
            'authors' => 'John Doe',
            'keyword' => 'tech',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['source' => 'Tech News']);
        $response->assertJsonFragment(['source' => 'Health News']);
    }

    /**
     * Test personalized feed for authenticated user.
     */
    public function testArticlesPersonalizedFeed()
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
     * Test the 'article' method with an existing article.
     */
    public function testArticleFound()
    {
        $article  = Article::factory()->create();
        $response = $this->withHeaders->getJson('/api/article/' . $article->id);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => $article->title]);
    }

    /**
     * Test the 'article' method with a non-existing article.
     */
    public function testArticleNotFound()
    {
        $response = $this->withHeaders->getJson('/api/article/9999');
        $response->assertStatus(404);
        $response->assertJson(['success' => false, 'message' => 'Article not found.']);
    }

    /**
     * Test the 'uniqueSources' method.
     */
    public function testUniqueSources()
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
     * Test the 'uniqueAuthors' method.
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
