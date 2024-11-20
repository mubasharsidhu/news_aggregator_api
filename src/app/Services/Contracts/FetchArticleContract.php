<?php

namespace App\Services\Contracts;

use App\DTOs\ArticleDTO;

interface FetchArticleContract
{
    /**
     * Normalize data to a standardized structure.
     *
     * @param array $article fetched article in raw formate
     *
     * @return ArticleDTO object.
     */
    public function normalizeData(array $article): ArticleDTO;
}
