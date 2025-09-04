<?php

namespace SRWieZ\ForgeHeartbeats\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use SRWieZ\ForgeHeartbeats\ForgeHeartbeatsServiceProvider;
use SRWieZ\ForgeHeartbeats\Http\Client\FakeForgeClient;
use SRWieZ\ForgeHeartbeats\Http\Client\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;
use Symfony\Component\Console\Output\BufferedOutput;
use function Termwind\renderUsing;

class TestCase extends Orchestra
{
    protected FakeForgeClient $forgeClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgeClient = new FakeForgeClient;
        $this->forgeClient->skipConfigValidation(true); // Skip validation by default for tests

        // Override the service provider binding
        $this->app->singleton(ForgeClientInterface::class, fn () => $this->forgeClient);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'SRWieZ\\ForgeHeartbeats\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        renderUsing(new BufferedOutput);

        TestKernel::clearScheduledCommands();
    }

    protected function tearDown(): void
    {
        $this->forgeClient->reset();
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
}
