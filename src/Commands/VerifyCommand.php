<?php

namespace SRWieZ\ForgeHeartbeats\Commands;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\Exceptions\InvalidConfigException;

class VerifyCommand extends Command
{
    protected $signature = 'forge-heartbeats:verify';

    protected $description = 'Verify Forge heartbeats configuration and connectivity';

    public function handle(ForgeClientInterface $forgeClient): int
    {
        $this->info('🔍 Verifying Forge heartbeats configuration...');

        try {
            $this->checkConfiguration();
            $this->checkConnectivity($forgeClient);

            $this->info('✅ Configuration verified successfully');

            return self::SUCCESS;
        } catch (InvalidConfigException $e) {
            $this->error('❌ Configuration Error: ' . $e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('❌ Error: ' . $e->getMessage());

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

    private function checkConnectivity(ForgeClientInterface $forgeClient): void
    {
        $this->info('🌐 Testing Forge API connectivity...');

        try {
            $heartbeats = $forgeClient->listHeartbeats();

            $count = count($heartbeats);
            $this->line('  ✓ Successfully connected to Forge API');
            $this->line("  ✓ Found {$count} existing heartbeat(s)");

            if ($count > 0) {
                $this->line("  ✓ Sample heartbeat: {$heartbeats[0]->name} ({$heartbeats[0]->status})");
            }
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();

            if ($statusCode === 401) {
                throw new InvalidConfigException('API authentication failed. Please check your FORGE_API_TOKEN.');
            } elseif ($statusCode === 403) {
                throw new InvalidConfigException('API access forbidden. Please check your permissions.');
            } elseif ($statusCode === 404) {
                throw new InvalidConfigException('API endpoint not found. Please check your organization, server, and site IDs.');
            } else {
                throw new InvalidConfigException("API request failed with status {$statusCode}: " . $e->getMessage());
            }
        }
    }
}
