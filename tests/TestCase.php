<?php

namespace SRWieZ\ForgeHeartbeats\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SRWieZ\ForgeHeartbeats\ForgeHeartbeatsServiceProvider;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\CreateHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\DeleteHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\GetHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\ListHeartbeatsRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\PingHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\UpdateHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;
use Symfony\Component\Console\Output\BufferedOutput;

use function Termwind\renderUsing;

class TestCase extends Orchestra
{
    protected MockClient $mockClient;

    protected array $heartbeats = [];

    protected int $nextId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupMockClient();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'SRWieZ\\ForgeHeartbeats\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        renderUsing(new BufferedOutput);

        TestKernel::clearScheduledCommands();
    }

    protected function tearDown(): void
    {
        $this->resetMockData();
        TestKernel::clearScheduledCommands();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            ForgeHeartbeatsServiceProvider::class,
        ];
    }

    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(Kernel::class, TestKernel::class);
    }

    public function getEnvironmentSetUp($app)
    {
        $app['env'] = 'testing';

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('forge-heartbeats.api_token', 'test-token');
        $app['config']->set('forge-heartbeats.organization', 'test-org');
        $app['config']->set('forge-heartbeats.server_id', 12345);
        $app['config']->set('forge-heartbeats.site_id', 67890);

        // Set queue configuration
        $app['config']->set('forge-heartbeats.queue.connection', 'sync');
        $app['config']->set('forge-heartbeats.queue.name', 'default');
    }

    protected function setupMockClient(): void
    {
        $mockData = [
            ListHeartbeatsRequest::class => MockResponse::make(['data' => array_values($this->heartbeats)]),
            CreateHeartbeatRequest::class => function () {
                $heartbeat = $this->createMockHeartbeat();

                return MockResponse::make(['data' => $heartbeat], 201);
            },
            GetHeartbeatRequest::class => function () {
                return MockResponse::make(['data' => reset($this->heartbeats) ?: $this->createMockHeartbeat()]);
            },
            UpdateHeartbeatRequest::class => function () {
                $heartbeat = reset($this->heartbeats) ?: $this->createMockHeartbeat();

                return MockResponse::make(['data' => $heartbeat]);
            },
            DeleteHeartbeatRequest::class => MockResponse::make('', 204),
            PingHeartbeatRequest::class => function (\Saloon\Http\PendingRequest $pendingRequest) {
                // Simulate failure for invalid URLs, success for test URLs
                $request = $pendingRequest->getRequest();
                $endpoint = $request->resolveEndpoint();
                if (str_contains($endpoint, 'invalid-url')) {
                    return MockResponse::make('', 500);
                }

                return MockResponse::make('', 200);
            },
        ];

        $this->mockClient = MockClient::global($mockData);
    }

    protected function resetMockData(): void
    {
        $this->heartbeats = [];
        $this->nextId = 1;
        MockClient::destroyGlobal();
    }

    protected function createMockHeartbeat(string $name = 'test-task', int $gracePeriod = 5, int $frequency = 0): array
    {
        $id = $this->nextId++;
        $heartbeat = [
            'id' => $id,
            'name' => $name,
            'status' => 'pending',
            'grace_period' => $gracePeriod,
            'frequency' => $frequency,
            'custom_frequency' => null,
            'ping_url' => "https://forge.laravel.com/api/heartbeat/ping/test{$id}",
        ];

        $this->heartbeats[$id] = $heartbeat;

        return $heartbeat;
    }

    protected function addMockHeartbeat(string $name, int $gracePeriod = 5, int $frequency = 0): array
    {
        return $this->createMockHeartbeat($name, $gracePeriod, $frequency);
    }
}
