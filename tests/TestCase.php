<?php

namespace SRWieZ\ForgeHeartbeats\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use SRWieZ\ForgeHeartbeats\ForgeHeartbeatsServiceProvider;
use SRWieZ\ForgeHeartbeats\Support\TaskMetadataRegistry;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;
use Symfony\Component\Console\Output\BufferedOutput;

use function Termwind\renderUsing;

class TestCase extends Orchestra
{
    protected const FORGE_API_URL = 'https://forge.laravel.com/api/orgs/test-org/servers/12345/sites/67890/heartbeats';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'SRWieZ\\ForgeHeartbeats\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        renderUsing(new BufferedOutput);

        TestKernel::clearScheduledCommands();
    }

    protected function tearDown(): void
    {
        TestKernel::clearScheduledCommands();
        TaskMetadataRegistry::clear();

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

    /**
     * Fake Forge API responses, keyed by "METHOD /path" relative to the site's heartbeats endpoint.
     *
     * @param  array<string, mixed>  $responses
     */
    protected function fakeForgeApi(array $responses): void
    {
        Http::fake(function (Request $request) use ($responses) {
            foreach ($responses as $route => $response) {
                [$method, $path] = explode(' ', $route, 2);

                if ($request->method() === $method && $request->url() === self::FORGE_API_URL . $path) {
                    return $response;
                }
            }
        });
    }
}
