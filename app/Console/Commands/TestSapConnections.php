<?php

namespace App\Console\Commands;

use App\Modules\ERPIntegration\Services\SapConnectorFactory;
use Illuminate\Console\Command;

class TestSapConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:test-connections {--type= : Test specific connector type (odata, rfc, idoc)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SAP connector connections (OData, RFC/BAPI, IDoc)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        if ($type) {
            $this->testSingleConnection($type);
        } else {
            $this->testAllConnections();
        }

        return Command::SUCCESS;
    }

    /**
     * Test a single connector type
     */
    protected function testSingleConnection(string $type): void
    {
        $this->info("Testing SAP {$type} connection...");

        try {
            $connector = SapConnectorFactory::create($type);
            $connected = $connector->testConnection();

            if ($connected) {
                $this->info("✓ {$type} connection successful!");
            } else {
                $this->error("✗ {$type} connection failed!");
            }
        } catch (\Exception $e) {
            $this->error("✗ {$type} connection error: {$e->getMessage()}");
        }
    }

    /**
     * Test all connector types
     */
    protected function testAllConnections(): void
    {
        $this->info('Testing all SAP connections...');
        $this->newLine();

        $results = SapConnectorFactory::testAllConnections();

        $rows = [];
        foreach ($results as $type => $result) {
            $rows[] = [
                $type,
                $result['connected'] ? '<fg=green>✓ Connected</>' : '<fg=red>✗ Failed</>',
                $result['message'],
            ];
        }

        $this->table(
            ['Type', 'Status', 'Message'],
            $rows
        );

        $allOk = collect($results)->every(fn ($result) => $result['connected'] === true);

        if ($allOk) {
            $this->info('✓ All SAP connections are working!');
        } else {
            $this->warn('⚠ Some SAP connections failed. Check configuration.');
        }
    }
}

