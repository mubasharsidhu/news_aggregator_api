<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LoggerService;
use App\Services\NewsServiceFactory;
use App\Models\Article;
use App\Jobs\FetchArticlesJob;

class FetchArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @param string `--source` (required): The source from which to fetch articles (e.g., `newsapi`).
     * @param int `--page` (optional, default: 1): The page number to retrieve articles from.
     * @param string `--from` (optional): The start date in Y-m-d format (e.g., "2024-11-20").
     * @param int `--delay` (optional, default: 12): The delay in seconds between each page iteration.
     */
    protected $signature = 'articles:fetch
        {--source= : The source of the articles}
        {--page=1 : The page number}
        {--from= : The start date}
        {--delay=12 : The delay in seconds between each page iteration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch articles from a specified news service and save them to our database.';

    /**
     * The NewsServiceFactory object
     *
     * @var NewsServiceFactory $newsService
     */
    protected $newsService;

    /**
     * Create a new class instance and set initial values
     *
     * @param NewsServiceFactory $newsService The news service to fetch from
     */
    public function __construct(NewsServiceFactory $newsService) {
        parent::__construct();
        $this->newsService = $newsService;
    }

    /**
     * Execute the console command.
     *
     * @param LoggerService $logger to log the information
     */
    public function handle(LoggerService $logger) { // TODO: schedule this call

        $source = $this->option('source');
        if (empty($source)) {
            $logger->error('The --source option is required.');
            return;
        }

        $logger->info("Fetching articles from {$source}...");

        $page = $this->option('page');
        $from = (string) $this->option('from');
        if (empty($from)) {
            $from = now()->subDay()->format('Y-m-d');
        }

        try {
            $articles = $this->newsService->create($source)->fetchArticles($page, $from);

            if (empty($articles['normalizedArticles'])) {
                $logger->info("No articles found on page {$page}. Fetching completed.");
                return;
            }

            $logger->info('Processing articles...');

            foreach ($articles['normalizedArticles'] as $article) {
                $article = (array) $article;
                Article::updateOrCreate(['articleUrl' => $article['articleUrl']], $article);
            }

            $logger->info("Page {$page} has been processed. Articles have been saved successfully.");

            if ($articles['currentPage'] === $articles['totalPages']) {
                $logger->info("All done for today! Fetching completed for {$source}.");
                return;
            }

            $nextPage = $page + 1;
            $logger->info("Fetching next page...");

            // Trigger the next iteration (page) of the command
            $delay = (int) $this->option('delay');

            FetchArticlesJob::dispatch($source, $nextPage, $from)->delay(now()->addSeconds($delay));

            $logger->info("Next page:{$nextPage} is dispatched and will be executed in {$delay} seconds.");

        } catch (\Exception $e) {
            $logger->error('Error fetching articles: ' . $e->getMessage());
        }
    }
}
