<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\NewsServiceFactory;
use App\Models\Article;
use App\Services\LoggerService;

use \Symfony\Component\Console\Output\ConsoleOutput;

class FetchArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @param string `--source` (required): The source from which to fetch articles (e.g., `newsapi`).
     * @param int `--page` (optional, default: 1): The page number to retrieve articles from.
     * @param string `--from` (optional): The start date in ISO 8601 format (e.g., "2024-11-20T00:00:00Z").
     * @param string `--to` (optional): The end date in ISO 8601 format (e.g., "2024-11-20T23:59:59Z").
     * The --from and --to params must be given together
     */
    protected $signature = 'articles:fetch
        {--source= : The source of the articles}
        {--page=1 : The page number}
        {--from= : The start date}
        {--to= : The end date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch articles from a specified news service and save them to the database';

    /**
     * The NewsServiceFactory object
     *
     * @var NewsServiceFactory $newsService
     */
    protected $newsService;

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

        $page   = $this->option('page');
        $from   = (string) $this->option('from');
        $to     = (string) $this->option('to');

        if ( (empty($from) && !empty($to)) || (!empty($from) && empty($to)) ) {
            $logger->error('Both --from and --to fields must be either filled together or left empty together.');
            return;
        }

        try {
            $articles = $this->newsService->create($source)->fetchArticles($page, $from, $to);
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
                $logger->info("All the articles processed, fetching completed.");
                return;
            }

            $nextPage = $page + 1;
            $logger->info("Fetching next page...");

            // Trigger the next iteration (page) of the command
            return Artisan::call(
                'articles:fetch',
                [
                    '--source' => $source,
                    '--page'   => $nextPage,
                    '--from'   => $from,
                    '--to'     => $to,
                ],
                new ConsoleOutput()
            );

        } catch (\Exception $e) {
            $logger->error('Error fetching articles: ' . $e->getMessage());
        }
    }
}
