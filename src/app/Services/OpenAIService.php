<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\LoggerService;
use Illuminate\Http\Client\RequestException;

class OpenAIService
{
    private $apiBaseUrl = 'https://api.openai.com/v1/chat/completions';

    public function summarize(?string $content, LoggerService $logger): ?string
    {
        if (empty($content)) {
            $logger->error('Article content is empty. Skipping OpenAI summarization.');
            return null;
        }

        $payload = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Summarize the article content in 3-4 lines.'],
                ['role' => 'user', 'content' => $content],
            ],
            'max_tokens'  => 150,
            'temperature' => 0.7,
        ];

        try {
            $response = Http::withToken(config('services.news_aggregator_openai_api_key'))
                ->timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($this->apiBaseUrl, $payload);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            $logger->error('OpenAI API error: ' . $response->body());

        } catch (RequestException $e) {
            $logger->error('OpenAI request failed: ' . $e->getMessage());
        }

        return null;
    }
}
