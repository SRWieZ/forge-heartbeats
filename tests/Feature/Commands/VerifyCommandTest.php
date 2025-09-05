<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\ListHeartbeatsRequest;

it('can verify configuration and connectivity', function () {
    // Explicitly set config values for this test
    config(['forge-heartbeats.api_token' => 'test-token']);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);

    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => [
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

    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => []]),
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
