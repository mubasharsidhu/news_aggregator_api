<?php

namespace App\Services;

class NewsServiceFactory
{
    /* private $baseUrls = [
        'newyork'  => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
    ]; */

    /* private $request_fields_mapping;

    private $response_fields_mapping;

    public function __construct()
    {
        $this->response_fields_mapping = [
            'newyork' => [
                'title'       => 'headline.main',
                'description' => 'lead_paragraph',
                'content'     => 'abstract',
                'publishedAt' => 'pub_date',
                'source'      => 'source',
                'author'      => 'byline.original',
                'imageUrl'    => 'multimedia.0.url',
                'articleUrl'  => 'web_url',
            ],
        ];
    }
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
