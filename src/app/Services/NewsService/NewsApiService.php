<?php

namespace App\Services\NewsService;

use App\Services\Contracts\FetchArticleContract;
use App\DTOs\ArticleDTO;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use App\Traits\Iso8601Checker;

class NewsapiService implements FetchArticleContract
{
    use Iso8601Checker;

    private $apiBaseUrl = 'https://newsapi.org/v2/everything?q=news';

    private $source;

    private $pageSize = 50;

    /**
     * Create a new class instance and set initial values
     *
     * @param string $source
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * Fetch Articles and normalize them to ArticleDTO objects
     *
     * @param int (required) $page The page number for pagination.
     * @param string (required) $from The start date Y-m-d format (e.g., "2024-11-20").
     *
     * @return ArticleDTO[] Articles array in standard formate
     */
    public function fetchArticles(int $page, string $from): array
    {
        $params   = $this->prepareParams($page, $from);
        $response = Http::get($this->apiBaseUrl, $params);

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

        return [
            'currentPage'        => $page,
            'totalPages'         => \ceil(($data['totalResults']/$this->pageSize)),
            'normalizedArticles' => $mappedArticles
        ];
    }

    /**
     * Prepare params for HTTP request
     *
     * @param int (required) $page The page number for pagination.
     * @param string (required) $from The start date Y-m-d format (e.g., "2024-11-20").
     *
     * @return array
     */
    private function prepareParams(int $page, string $from): array
    {
        $params = [
            'apiKey'   => config('services.news_aggregator_api_keys.newsapi'),
            'q'        => 'news',
            'from'     => $from,
            'page'     => $page,
            'pageSize' => $this->pageSize,
        ];

        return $params;
    }

    /**
     * Normalize data to a standardized structure.
     *
     * @param array $article The article data as an associative array.
     *
     * @return ArticleDTO The normalized article as an ArticleDTO object.
     */
    public function normalizeData(array $article): ArticleDTO
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
