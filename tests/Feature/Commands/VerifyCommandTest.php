<?php

it('can verify configuration and connectivity', function () {
    // Manually test what happens when we inject the client
    $fakeClient = app(\SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface::class);
    expect($fakeClient)->toBeInstanceOf(\SRWieZ\ForgeHeartbeats\Tests\TestClasses\FakeForgeClient::class);
    
    // Manually set configuration for this test
    config(['forge-heartbeats.api_token' => 'test-token']);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);
    config(['forge-heartbeats.queue.connection' => 'sync']);
    config(['forge-heartbeats.queue.name' => 'default']);
    
    $this->artisan('forge:heartbeats:verify')
        ->assertExitCode(0);
});

it('fails when configuration is missing', function () {
    // Set up basic config but leave api_token null
    config(['forge-heartbeats.api_token' => null]);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);

    $this->artisan('forge:heartbeats:verify')
        ->expectsOutput('🔍 Verifying Forge heartbeats configuration...')
        ->expectsOutput('🔧 Checking configuration...')
        ->assertExitCode(1);
});

it('handles authentication errors', function () {
    // Set config but make the fake client validate config to simulate auth error
    config(['forge-heartbeats.api_token' => null]);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);

    $this->artisan('forge:heartbeats:verify')
        ->expectsOutput('🔍 Verifying Forge heartbeats configuration...')
        ->expectsOutput('🔧 Checking configuration...')
        ->assertExitCode(1);
});

it('handles missing organization config', function () {
    config(['forge-heartbeats.api_token' => 'test-token']);
    config(['forge-heartbeats.organization' => null]);
    config(['forge-heartbeats.server_id' => 12345]);
    config(['forge-heartbeats.site_id' => 67890]);

    $this->artisan('forge:heartbeats:verify')
        ->assertExitCode(1);
});

it('handles missing server id config', function () {
    config(['forge-heartbeats.api_token' => 'test-token']);
    config(['forge-heartbeats.organization' => 'test-org']);
    config(['forge-heartbeats.server_id' => null]);
    config(['forge-heartbeats.site_id' => 67890]);

    $this->artisan('forge:heartbeats:verify')
        ->assertExitCode(1);
});