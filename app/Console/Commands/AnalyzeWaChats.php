<?php

namespace App\Console\Commands;

use App\Services\WaChatArchive;
use Illuminate\Console\Command;

/**
 * Turns the archived WhatsApp conversations into structured sale / lead / task / payment rows.
 * Runs on a schedule; safe to run by hand at any time.
 */
class AnalyzeWaChats extends Command
{
    protected $signature = 'wa:analyze-chats
                            {--batch=60 : Messages per model call}
                            {--loops=5 : How many batches to process in this run}
                            {--chat= : Restrict to one chat JID}';

    protected $description = 'Extract sales, leads, tasks and payments from archived WhatsApp chats';

    public function handle(): int
    {
        $batch = max(5, (int) $this->option('batch'));
        $loops = max(1, (int) $this->option('loops'));
        $chat  = $this->option('chat') ?: null;

        $totalMessages = 0;
        $totalInsights = 0;

        for ($i = 0; $i < $loops; $i++) {
            $result = WaChatArchive::analyzePending($batch, $chat);

            if ($result['messages'] === 0) {
                break;
            }

            $totalMessages += $result['messages'];
            $totalInsights += $result['insights'];
            $this->line("batch " . ($i + 1) . ": {$result['messages']} messages -> {$result['insights']} insights");
        }

        $this->info("done: {$totalMessages} messages analysed, {$totalInsights} insights extracted.");

        return self::SUCCESS;
    }
}
