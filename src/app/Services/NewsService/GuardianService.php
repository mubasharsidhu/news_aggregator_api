<?php

namespace App\Services\NewsService;

use App\Services\Contracts\FetchArticleContract;
use App\DTOs\ArticleDTO;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use App\Traits\Iso8601Checker;

class GuardianService implements FetchArticleContract
{
    use Iso8601Checker;

    private $apiBaseUrl = 'https://content.guardianapis.com/search';

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
        if (!isset($this->apiBaseUrl)) {
            throw new \Exception("Unsupported service: " . $this->source);
        }

        $params   = $this->prepareParams($page, $from);
        $response = Http::get($this->apiBaseUrl, $params);

        if ($response->failed()) {
            throw new \Exception("Failed to fetch articles from {$this->source}: " . $response->body());
        }

        $data = $response->json();
        if (!isset($data['response']['results']) || \count($data['response']['results'])<=0) {
            return [];
        }

        $mappedArticles = [];
        foreach ($data['response']['results'] as $article) {
            $mappedArticles[] = $this->normalizeData($article);
        }

        return [
            'currentPage'        => $data['response']['currentPage'],
            'totalPages'         => $data['response']['pages'],
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
            'api-key'     => config('services.news_aggregator_api.guardian_api_key'),
            'show-fields' => 'standfirst,body,publication,byline,thumbnail',
            'from-date'   => $from,
            'page'        => $page,
            'page-size'   => $this->pageSize,
        ];

        return $params;
    }

    /**
     * Normalize data to a standardized structure.
     *
     * @param array $article fetched single-article in API's raw formate.
     *
     * @return ArticleDTO The normalized article as an ArticleDTO object.
     */
    public function normalizeData(array $article): ArticleDTO
    {
        $dto              = new ArticleDTO();
        $dto->title       = isset( $article['webTitle']) ? $article['webTitle'] : '';
        $dto->description = isset( $article['fields']['standfirst']) ? $article['fields']['standfirst'] : '';
        $dto->content     = isset( $article['fields']['body']) ? $article['fields']['body'] : '';
        $dto->source      = isset( $article['fields']['publication']) ? $article['fields']['publication'] : '';
        $dto->author      = isset( $article['fields']['byline']) ? $article['fields']['byline'] : '';
        $dto->imageUrl    = isset( $article['fields']['thumbnail']) ? $article['fields']['thumbnail'] : '';
        $dto->articleUrl  = isset( $article['webUrl']) ? $article['webUrl'] : '';
        $dto->publishedAt = isset( $article['webPublicationDate']) ? Carbon::parse($article['webPublicationDate'])->toDateTimeString() : '';
        $dto->apiSource   = $this->source;

        return $dto;
    }

}
