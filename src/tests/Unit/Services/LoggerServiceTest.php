<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

use App\Services\LoggerService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutput;
use Mockery;


class LoggerServiceTest extends TestCase
{
    protected $service;
    protected $consoleOutput;
    protected $logFacade;

    public function setUp(): void
    {
        parent::setUp();
        $this->service       = new LoggerService();
        $this->consoleOutput = Mockery::mock(ConsoleOutput::class);
        $this->logFacade     = Mockery::mock('alias:'. Log::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function testLogWhenRunningInConsole()
    {
        $this->app->instance('app', $this->mockRunningInConsole(true));

        $message = 'Test Message';
        $level   = 'info';
        $channel = 'test_channel';
        $this->consoleOutput->shouldReceive('writeln')->with("<{$level}>{$message}</{$level}>");

        $this->logFacade->shouldReceive('channel')->with($channel)->andReturnSelf();
        $this->logFacade->shouldReceive('info')->with($message);

        $this->service->log($level, $message, $channel);
        $this->expectNotToPerformAssertions();
    }


    public function testLogWhenNotRunningInConsole()
    {
        $this->app->instance('app', $this->mockRunningInConsole(false));

        $this->consoleOutput->shouldReceive('writeln')->never();
        $message = 'Test Message';
        $level   = 'info';
        $channel = 'test_channel';
        $this->logFacade->shouldReceive('channel')->with($channel)->andReturnSelf();
        $this->logFacade->shouldReceive('info')->with($message);

        $this->service->log($level, $message, $channel);
        $this->expectNotToPerformAssertions();

    }

    public function testLoggerInfoMethod()
    {
        $message = 'Info Message';
        $channel = 'info_channel';
        $this->service = Mockery::mock(LoggerService::class)->makePartial();
        $this->service->shouldReceive('log')->with('info', $message, $channel)->once();

        $this->service->info($message, $channel);
        $this->expectNotToPerformAssertions();
    }

    public function testLoggerErrorMethod()
    {
        $message = 'Error Message';
        $channel = 'error_channel';
        $this->service = Mockery::mock(LoggerService::class)->makePartial();
        $this->service->shouldReceive('log')->with('error', $message, $channel)->once();

        $this->service->error($message, $channel);
        $this->expectNotToPerformAssertions();
    }

    protected function mockRunningInConsole($return)
    {
        $app = Mockery::mock('Illuminate\Foundation\Application');
        $app->shouldReceive('runningInConsole')->andReturn($return);
        return $app;
    }
}
