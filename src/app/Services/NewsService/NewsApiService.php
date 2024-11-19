<?php

namespace App\Services\NewsService;

use App\Services\Contracts\FetchArticleContract;
use App\DTOs\ArticleDTO;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NewsApiService implements FetchArticleContract
{

    private $apiBaseUrl = 'https://newsapi.org/v2/everything?q=news';

    private $baseParams;

    private $source;

    /**
     * Create a new class instance and set initial values
     *
     * @param string $source
     */
    public function __construct(string $source)
    {
        $this->source     = $source;
        $this->baseParams = [
            'apiKey' => config('services.newsapi_news.key'),
            'q'      => 'news',
            'from'   => now()->subMonth()->toIso8601String(),
            'to'     => now()->toIso8601String(),
        ];
    }

    /**
     * Fetch Articles and normalize them to ArticleDTO objects
     *
     * @param array $params
     * @return ArticleDTO[] Articles array in standard formate
     */
    public function fetchArticles(array $params=[]): array
    {
        if (!isset($this->apiBaseUrl)) {
            throw new \Exception("Unsupported service: " . $this->source);
        }

        $response = Http::get($this->apiBaseUrl, array_merge($params, $this->baseParams));

        if ($response->failed()) {
            throw new \Exception("Failed to fetch articles from {$this->source}: " . $response->body());
        }

        $data = $response->json();
        if (!isset($data['articles']) || \count($data['articles'])<=0) {
            return [];
        }

        $mappedArticles = [];
        foreach ($data['articles'] as $article) {
            if ('[Removed]' === $article['title']) {
                continue;
            }
            $mappedArticles[] = $this->normalizeData($article);
        }

        return $mappedArticles;
    }

    public function normalizeData($article): ArticleDTO
    {
        $dto              = new ArticleDTO();
        $dto->title       = isset( $article['title']) ? $article['title'] : '';
        $dto->description = isset( $article['description']) ? $article['description'] : '';
        $dto->content     = isset( $article['content']) ? $article['content'] : '';
        $dto->source      = isset( $article['source']['name']) ? $article['source']['name'] : '';
        $dto->author      = isset( $article['author']) ? $article['author'] : '';
        $dto->imageUrl    = isset( $article['urlToImage']) ? $article['urlToImage'] : '';
        $dto->articleUrl  = isset( $article['url']) ? $article['url'] : '';
        $dto->publishedAt = isset( $article['publishedAt']) ? Carbon::parse($article['publishedAt'])->toDateTimeString() : '';
        $dto->apiSource   = $this->source;

        return $dto;
    }

}
