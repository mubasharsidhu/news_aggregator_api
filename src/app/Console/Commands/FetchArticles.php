<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
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
    public function handle(LoggerService $logger) {

        $source = $this->option('source');
        if (empty($source)) {
            $logger->error('The --source option is required.');
            return 1;
        }

        $news_aggregators = array_keys( config('services.news_aggregator_api_keys') );
        if (!in_array($source, $news_aggregators)) {
            $logger->error('Invalid source: The --source can only contain one of these values: '.\implode(', ', $news_aggregators));
            return 1;
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
                return 0;
            }

            $logger->info('Processing articles...');

            foreach ($articles['normalizedArticles'] as $article){
                $article   = (array) $article;
                $validator = $this->validateArticle($article);
                if ($validator->fails()) {
                    $logger->error(
                        'Validation failed: ' . json_encode($validator->errors() . ' | Article: ' . json_encode($article) )
                    );
                    continue;
                }

                Article::updateOrCreate(['articleUrl' => $article['articleUrl']], $article);
            }

            $logger->info("Page {$page} has been processed. Articles have been saved successfully.");

            if ($articles['currentPage'] === $articles['totalPages']) {
                $logger->info("All done for today! Fetching completed for {$source}.");
                return 0;
            }

            $nextPage = $page + 1;
            $logger->info("Fetching next page...");

            // Trigger the next iteration (page) of the command
            $delay = (int) $this->option('delay');

            FetchArticlesJob::dispatch($source, $nextPage, $from)->delay(now()->addSeconds($delay));

            $logger->info("Next page:{$nextPage} is dispatched and will be executed in {$delay} seconds.");

            $this->info('All done!');
            return 0;

        } catch (\Exception $e) {
            $logger->error('Error fetching articles: ' . $e->getMessage());

            $this->info('Error state.');
            return 1;
        }
    }

    public function validateArticle($article)
    {
        return Validator::make($article, [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'content'     => 'nullable|string',
            'source'      => 'required|string|max:255',
            'author'      => 'nullable|string|max:255',
            'imageUrl'    => 'nullable',
            'articleUrl'  => 'required|url|max:255|unique:articles,articleUrl',
            'publishedAt' => 'required|date',
            'apiSource'   => 'required|string|max:255',
        ]);
    }
}
