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
            'apiKey'   => config('services.newsapi_news.key'),
            'from'     => now()->subHour()->toIso8601String(),
            'to'       => now()->toIso8601String(),
            'q'        => 'news',
            'pageSize' => 50,
        ];
    }

    /**
     * Fetch Articles and normalize them to ArticleDTO objects
     *
     * @param int (required) $page The page number for pagination.
     * @param date (optional) $from The start date in ISO 8601 format (e.g., "2024-11-20T00:00:00Z").
     * @param date (optional) $to The end date in ISO 8601 format (e.g., "2024-11-20T23:59:59Z").
     *
     * @return ArticleDTO[] Articles array in standard formate
     */
    public function fetchArticles(int $page, $from='', $to=''): array
    {
        if (!isset($this->apiBaseUrl)) {
            throw new \Exception("Unsupported service: " . $this->source);
        }

        $params   = $this->prepareParams($page, $from, $to);
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

        return $mappedArticles;
    }

    /**
     * Normalize data to a standardized structure.
     *
     * @param array $article The article data as an associative array.
     * @return ArticleDTO The normalized article as an ArticleDTO object.
     */
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


    /**
     * Prepare params for HTTP request
     *
     * @param int (required) $page The page number for pagination.
     * @param date (optional) $from The start date in ISO 8601 format (e.g., "2024-11-20T00:00:00Z").
     * @param date (optional) $to The end date in ISO 8601 format (e.g., "2024-11-20T23:59:59Z").
     *
     * @return array
     */
    private function prepareParams($page, $from, $to): array
    {
        $this->baseParams['page'] = $page;

        if (!empty($from) && $this->isIso8601String($from) && !empty($to) && $this->isIso8601String($to)) {
            $this->baseParams['from'] = $from;
            $this->baseParams['to']   = $to;
        }

        return $this->baseParams;
    }

    /**
     * Check if a string is in ISO 8601 format.
     *
     * @param string $date
     * @return bool
     */
    function isIso8601String(string $date): bool
    {
        try {
            $parsedDate = new \DateTime($date);
            // Convert back to ISO 8601 and compare to ensure valid format
            return $parsedDate->format(\DateTime::ATOM) === $date;
        } catch (\Exception $e) {
            return false;
        }
    }

}
