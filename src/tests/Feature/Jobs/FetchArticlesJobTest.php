<?php

namespace Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Jobs\FetchArticlesJob;
use App\Services\LoggerService;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class FetchArticlesJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if the job executes the Artisan command with the correct parameters.
     */
    public function testJobHandleExecutesArtisanCommandSuccess(): void
    {
        $loggerMock = Mockery::mock(LoggerService::class);
        $loggerMock->shouldReceive('info')->once()->with('Fetching articles from newsapi...');
        $loggerMock->shouldReceive('error')->never();

        Artisan::shouldReceive('call')
            ->once()
            ->with(
                'articles:fetch',
                [
                    '--source' => 'newsapi',
                    '--page'   => 1,
                    '--from'   => '2024-11-20',
                ],
                Mockery::type(\Symfony\Component\Console\Output\ConsoleOutput::class)
            );

        $job = new FetchArticlesJob('newsapi', 1, '2024-11-20');

        /** @var LoggerService $loggerMock */
        $job->handle($loggerMock);
    }

    /**
     * Test if the job handles exceptions and logs errors.
     */
    public function testJobHandleExecutesArtisanCommandExceptions(): void
    {
        $loggerMock = Mockery::mock(LoggerService::class);
        $loggerMock->shouldReceive('info')->once()->with('Fetching articles from newsapi...');
        $loggerMock->shouldReceive('error')->once()->with(Mockery::type('string'));

        Artisan::shouldReceive('call')
            ->once()
            ->andThrow(new \Exception('Simulated error'));

        $job = new FetchArticlesJob('newsapi', 1, '2024-11-20');

        /** @var LoggerService $loggerMock */
        $job->handle($loggerMock);

    }
}
