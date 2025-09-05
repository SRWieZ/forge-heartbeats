<?php

namespace SRWieZ\ForgeHeartbeats\Commands;

use Illuminate\Console\Command;
use Saloon\Exceptions\Request\RequestException;
use SRWieZ\ForgeHeartbeats\Http\Client\Exceptions\InvalidConfigException;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\ForgeHeartbeatsConnector;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\ListHeartbeatsRequest;

class VerifyCommand extends Command
{
    protected $signature = 'forge-heartbeats:verify';

    protected $description = 'Verify Forge heartbeats configuration and connectivity';

    public function handle(ForgeHeartbeatsConnector $forgeConnector): int
    {
        $this->info('🔍 Verifying Forge heartbeats configuration...');

        try {
            $this->checkConfiguration();
            $this->checkConnectivity($forgeConnector);

            $this->info('✅ Configuration verified successfully');

            return self::SUCCESS;
        } catch (InvalidConfigException $e) {
            // Configuration or connectivity errors already show friendly messages
            return self::FAILURE;
        }
    }

    private function checkConfiguration(): void
    {
        $this->info('🔧 Checking configuration...');

        $requiredConfigs = [
            'forge-heartbeats.api_token' => 'FORGE_API_TOKEN',
            'forge-heartbeats.organization' => 'FORGE_ORGANIZATION',
            'forge-heartbeats.server_id' => 'FORGE_SERVER_ID',
            'forge-heartbeats.site_id' => 'FORGE_SITE_ID',
        ];

        foreach ($requiredConfigs as $config => $envVar) {
            $value = config($config);

            if (empty($value)) {
                throw new InvalidConfigException("Missing configuration: {$config}. Please set {$envVar} in your .env file.");
            }

            $this->line("  ✓ {$config}: " . (is_string($value) ? str_repeat('*', min(strlen($value), 8)) : $value));
        }

        // Check queue configuration
        $queueConnection = config('forge-heartbeats.queue.connection', 'default');
        $queueName = config('forge-heartbeats.queue.name', 'default');

        $this->line("  ✓ Queue connection: {$queueConnection}");
        $this->line("  ✓ Queue name: {$queueName}");
    }

    private function checkConnectivity(ForgeHeartbeatsConnector $forgeConnector): void
    {
        $this->info('🌐 Testing Forge API connectivity...');

        try {
            $request = new ListHeartbeatsRequest;
            $response = $forgeConnector->send($request);

            $response->throw();

            $heartbeats = $request->createDtoFromResponse($response);
            $count = count($heartbeats);
            $this->line('  ✓ Successfully connected to Forge API');
            $this->line("  ✓ Found {$count} existing heartbeat(s)");

            if ($count > 0) {
                $this->line("  ✓ Sample heartbeat: {$heartbeats[0]->name} ({$heartbeats[0]->status})");
            }
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->status();

            $this->error('❌ Failed to connect to Forge API');

            if ($statusCode === 401) {
                $this->line('  Status: 401 Unauthorized - Please check your FORGE_API_TOKEN');
            } elseif ($statusCode === 403) {
                $this->line('  Status: 403 Forbidden - Please check your API permissions');
            } elseif ($statusCode === 404) {
                $this->line('  Status: 404 Not Found - Please check your organization, server, and site IDs');
            } else {
                $this->line("  Status: {$statusCode} - " . ($e->getMessage() ?: 'Unknown error'));
            }

            throw new InvalidConfigException('Forge API connectivity check failed.');
        }
    }
}
