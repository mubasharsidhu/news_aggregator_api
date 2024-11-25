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

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service       = new LoggerService();
        $this->consoleOutput = Mockery::mock(ConsoleOutput::class);
        $this->logFacade     = Mockery::mock('alias:'. Log::class);
    }

    /**
     * Clean up after each test method.
     *
     * @return void
     */
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test the logging functionality when running in the console.
     *
     * @return void
     */
    public function testLogWhenRunningInConsole(): void
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

    /**
     * Test the logging functionality when not running in the console.
     *
     * @return void
     */
    public function testLogWhenNotRunningInConsole(): void
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

    /**
     * Test the 'info' method of the LoggerService.
     *
     * @return void
     */
    public function testLoggerInfoMethod(): void
    {
        $message = 'Info Message';
        $channel = 'info_channel';
        $this->service = Mockery::mock(LoggerService::class)->makePartial();
        $this->service->shouldReceive('log')->with('info', $message, $channel)->once();

        $this->service->info($message, $channel);
        $this->expectNotToPerformAssertions();
    }

    /**
     * Test the 'error' method of the LoggerService.
     *
     * @return void
     */
    public function testLoggerErrorMethod(): void
    {
        $message = 'Error Message';
        $channel = 'error_channel';
        $this->service = Mockery::mock(LoggerService::class)->makePartial();
        $this->service->shouldReceive('log')->with('error', $message, $channel)->once();

        $this->service->error($message, $channel);
        $this->expectNotToPerformAssertions();
    }

    /**
     * Mock the 'runningInConsole' method of the app instance.
     *
     * @param bool $return Value to return for 'runningInConsole' mock.
     *
     * @return \Mockery\MockInterface Mocked app instance
     */
    protected function mockRunningInConsole($return): \Mockery\MockInterface
    {
        $app = Mockery::mock('Illuminate\Foundation\Application');
        $app->shouldReceive('runningInConsole')->andReturn($return);
        return $app;
    }
}
