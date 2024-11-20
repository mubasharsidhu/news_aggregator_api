<?php

namespace App\Services;

use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\Log;

class LoggerService
{
    /**
     * Log the message to both terminal (info) and a specific log channel.
     *
     * @param string $level
     * @param string $message
     * @param string $channel
     */
    public function log($level, $message, $channel='news_api_logs'): void
    {
        // Log to terminal (when running via Artisan command)
        if (app()->runningInConsole()) {
            (new ConsoleOutput())->writeln("<{$level}>{$message}</{$level}>");
        }

        // Log to custom log channel for persistent logging
        Log::channel($channel)->info($message);
    }

    /**
     * Log message as info.
     *
     * @param string $message
     * @param string $channel
     */
    public function info($message, $channel='news_api_logs'): void
    {
        $this->log('info', $message, $channel);
    }

    /**
     * Log message as error.
     *
     * @param string $message
     * @param string $channel
     */
    public function error($message, $channel='news_api_logs'): void
    {
        $this->log('error', $message, $channel);
    }
}
