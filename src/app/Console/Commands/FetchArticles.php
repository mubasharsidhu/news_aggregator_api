<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\NewsServiceFactory;
use App\Models\Article;

class FetchArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:fetch {source} {page?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch articles from a specified news service and save them to the database';

    protected $newsService;

    public function __construct(NewsServiceFactory $newsService) {
        parent::__construct();
        $this->newsService = $newsService;
    }

    /**
     * Execute the console command.
     */
    public function handle() { // TODO: schedule this call
        $this->info('Fetching articles from NewsAPI...');

        $source  = $this->argument('source');
        $page    = $this->argument('page') ?: 1;
        $perPage = 50;

        try {
            $articles = $this->newsService->create($source)->fetchArticles([
                'pageSize' => $perPage,
                'page'     => $page,
            ]);

            if (empty($articles)) {
                $this->info("No articles found on page {$page}. Fetching completed.");
                return; // Stop if no articles are returned
            }

            $this->info('Processing articles...');

            foreach ($articles as $article) {
                $article = (array) $article;
                Article::updateOrCreate(['articleUrl' => $article['articleUrl']], $article);
            }

            $this->info("Page {$page} has been processed. Articles have been saved successfully.");

            $nextPage = $page + 1;
            $this->info("Fetching next page...");

            // Trigger the next iteration (page) of the command
            Artisan::call('articles:fetch', [
                'source' => $source,
                'page'   => $nextPage,
            ]);
        } catch (\Exception $e) {
            $this->error('Error fetching articles: ' . $e->getMessage());
        }
    }
}
