<?php

namespace App\Services;

class NewsServiceFactory
{
    /**
     * Creates and returns an instance of a specific news API source.
     *
     * @param string $source (required): The source name from which to fetch articles (e.g., `newsapi`).
     *
     * @return instance of a specific News Api service class
     */
    public function create($source)
    {
        $className = 'App\\Services\\NewsService\\' . ucfirst($source) . 'Service';

        if (class_exists($className)) {
            return new $className($source);  // Instantiate the appropriate service class dynamically
        }

        throw new \Exception("API Service class $className not found.");
    }
}
