<?php

use Illuminate\Support\Facades\Http;

it('can verify configuration and connectivity', function () {
    // Explicitly set config values for this test
    config(['forge-heartbeats.api_token' => 'test-token']);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);

    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => [
            [
                'id' => 1,
                'name' => 'test-heartbeat',
                'status' => 'active',
                'grace_period' => 5,
                'frequency' => 1,
                'custom_frequency' => null,
                'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test1',
            ],
        ]]),
    ]);

    $this->artisan('forge-heartbeats:verify')
        ->expectsOutput('🔍 Verifying Forge heartbeats configuration...')
        ->expectsOutput('🔧 Checking configuration...')
        ->expectsOutput('🌐 Testing Forge API connectivity...')
        ->expectsOutput('✅ Configuration verified successfully')
        ->assertExitCode(0);
});

it('handles empty heartbeats response', function () {
    // Explicitly set config values for this test
    config(['forge-heartbeats.api_token' => 'test-token']);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);

    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => []]),
    ]);

    $this->artisan('forge-heartbeats:verify')
        ->expectsOutput('🔍 Verifying Forge heartbeats configuration...')
        ->expectsOutput('🔧 Checking configuration...')
        ->expectsOutput('🌐 Testing Forge API connectivity...')
        ->expectsOutput('  ✓ Successfully connected to Forge API')
        ->expectsOutput('  ✓ Found 0 existing heartbeat(s)')
        ->expectsOutput('✅ Configuration verified successfully')
        ->assertExitCode(0);
});

it('reports an invalid API token', function () {
    $this->fakeForgeApi([
        'GET /' => Http::response(['message' => 'Unauthenticated.'], 401),
    ]);

    $this->artisan('forge-heartbeats:verify')
        ->expectsOutput('❌ Failed to connect to Forge API')
        ->expectsOutput('  Status: 401 Unauthorized - Please check your FORGE_API_TOKEN')
        ->assertExitCode(1);
});

it('uses the configured API base URL', function () {
    config(['forge-heartbeats.api.base_url' => 'https://forge-proxy.test/api/']);

    Http::fake([
        'https://forge-proxy.test/api/orgs/test-org/servers/12345/sites/67890/heartbeats/' => Http::response(['data' => []]),
    ]);

    $this->artisan('forge-heartbeats:verify')
        ->expectsOutput('  ✓ Successfully connected to Forge API')
        ->assertExitCode(0);
});
