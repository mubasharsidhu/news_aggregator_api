<?php

namespace App\Services\Contracts;

use App\DTOs\ArticleDTO;

interface FetchArticleContract
{
    /**
     * Fetch and Normalize data to a standardized structure.
     *
     * @param array $params
     * @return ArticleDTO object.
     */
    public function normalizeData(array $article): ArticleDTO;
}
