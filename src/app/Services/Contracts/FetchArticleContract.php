<?php

namespace App\Services\Contracts;

use App\DTOs\ArticleDTO;

interface FetchArticleContract
{
    /**
     * Fetch Articles and normalize them to ArticleDTO objects
     *
     * @param int (required) $page The page number for pagination.
     * @param string (required) $from The start date Y-m-d format (e.g., "2024-11-20").
     *
     * @return array {
     *     currentPage: int,
     *     totalPages: int,
     *     normalizedArticles: ArticleDTO[]
     * }
     * An associative array containing the current page, total pages, and an array of normalized ArticleDTO objects.
     */
    public function fetchArticles(int $page, string $from): array;

    /**
     * Normalize data to a standardized structure.
     *
     * @param array $article fetched, single-article, in API's raw formate.
     *
     * @return ArticleDTO object.
     */
    public function normalizeData(array $article): ArticleDTO;
}
