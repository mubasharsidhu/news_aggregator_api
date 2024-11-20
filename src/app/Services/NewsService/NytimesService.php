<?php

namespace App\Services\NewsService;

use App\Services\Contracts\FetchArticleContract;
use App\DTOs\ArticleDTO;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use App\Traits\Iso8601Checker;

class NYtimesService implements FetchArticleContract
{
    use Iso8601Checker;

    private $apiBaseUrl = 'https://api.nytimes.com/svc/search/v2/articlesearch.json';

    private $source;

    private $pageSize = 10; // NewYork times returns 10 records at a time

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

        $page     = $page - 1; // New york times Api's page count starts from 0. Decrementing to standardize
        $params   = $this->prepareParams($page, $from);
        $response = Http::get($this->apiBaseUrl, $params);

        if ($response->failed()) {
            throw new \Exception("Failed to fetch articles from {$this->source}: " . $response->body());
        }

        $data = $response->json();
        if (!isset($data['response']['docs']) || \count($data['response']['docs'])<=0) {
            return [];
        }

        $mappedArticles = [];
        foreach ($data['response']['docs'] as $article) {
            $mappedArticles[] = $this->normalizeData($article);
        }

        return [
            'currentPage'        => \ceil(($data['response']['meta']['offset']/$this->pageSize)) + 1, // New york times Api's page count starts from 0. Incrementing to standardize
            'totalPages'         => \ceil(($data['response']['meta']['hits']/$this->pageSize)),
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
            'api-key'    => config('services.news_aggregator_api.nytimes_api_key'),
            'begin_date' => \str_replace('-', '', $from),
            'fl'         => 'headline,lead_paragraph,abstract,pub_date,source,byline,multimedia,web_url,print_page',
            'page'       => $page,
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
        $dto->title       = isset( $article['headline']['main']) ? $article['headline']['main'] : '';
        $dto->description = isset( $article['lead_paragraph']) ? $article['lead_paragraph'] : '';
        $dto->content     = isset( $article['abstract']) ? $article['abstract'] : '';
        $dto->source      = isset( $article['source']) ? $article['source'] : '';
        $dto->author      = isset( $article['byline']['original']) ? $article['byline']['original'] : '';
        $dto->imageUrl    = isset( $article['multimedia'][0]['url']) ? $article['multimedia'][0]['url'] : '';
        $dto->articleUrl  = isset( $article['web_url']) ? $article['web_url'] : '';
        $dto->publishedAt = isset( $article['pub_date']) ? Carbon::parse($article['pub_date'])->toDateTimeString() : '';
        $dto->apiSource   = $this->source;

        return $dto;
    }

}
