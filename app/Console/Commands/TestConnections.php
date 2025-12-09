<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ConnectionService;

class TestConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all database, Redis, cache, and queue connections with fallback logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing all connections...');
        $this->newLine();

        $status = ConnectionService::testAllConnections();

        $this->table(
            ['Connection', 'Status'],
            [
                ['Database', $this->formatStatus($status['database'])],
                ['Redis', $this->formatStatus($status['redis'])],
                ['Cache', $this->formatStatus($status['cache'])],
                ['Queue', $this->formatStatus($status['queue'])],
            ]
        );

        $allOk = collect($status)->every(fn ($status) => $status === true);

        if ($allOk) {
            $this->info('✓ All connections are working!');
            return Command::SUCCESS;
        }

        $this->warn('⚠ Some connections failed. Check logs for details.');
        return Command::FAILURE;
    }

    /**
     * Format status for display
     *
     * @param mixed $status
     * @return string
     */
    private function formatStatus($status): string
    {
        if ($status === true) {
            return '<fg=green>✓ Connected</>';
        }

        return '<fg=red>✗ Failed: ' . $status . '</>';
    }
}

