<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Services\LoggerService;

class FetchArticlesJob implements ShouldQueue
{
    use Queueable;

    protected $source;
    protected $page;
    protected $from;

    /**
     * Create a new job instance.
     *
     * @param string $source (required): The source from which to fetch articles (e.g., `newsapi`).
     * @param int $page (required) The page number for pagination.
     * @param string $from (required) The start date Y-m-d format (e.g., "2024-11-20").
     */
    public function __construct(string $source, int $page, string $from)
    {
        $this->source = $source;
        $this->page   = $page;
        $this->from   = $from;
    }

    /**
     * Execute the job.
     */
    public function handle(LoggerService $logger): void
    {
        $logger->info("Fetching articles from {$this->source}...");

        try {
            Artisan::call(
                'articles:fetch',
                [
                    '--source' => $this->source,
                    '--page'   => $this->page,
                    '--from'   => $this->from,
                ],
                new ConsoleOutput()
            );
        } catch (\Exception $e) {
            $logger->error('Error fetching articles: ' . $e->getMessage());
        }
    }
}
