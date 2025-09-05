<?php

namespace SRWieZ\ForgeHeartbeats\Http\Integrations\Forge;

use Composer\InstalledVersions;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class ForgeHeartbeatsConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        protected string $apiToken,
        protected string $organization,
        protected int $serverId,
        protected int $siteId,
    ) {}

    public function resolveBaseUrl(): string
    {
        return sprintf(
            'https://forge.laravel.com/api/orgs/%s/servers/%d/sites/%d/heartbeats',
            $this->organization,
            $this->serverId,
            $this->siteId
        );
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiToken);
    }

    protected function defaultHeaders(): array
    {
        $version = InstalledVersions::getVersion('srwiez/forge-heartbeats') ?? 'dev-main';

        return [
            'Content-Type' => 'application/json',
            'User-Agent' => 'ForgeHeartbeats/' . $version . ' (srwiez/forge-heartbeats composer package)',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => config('forge-heartbeats.api.timeout', 30),
        ];
    }

    public function getOrganization(): string
    {
        return $this->organization;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getSiteId(): int
    {
        return $this->siteId;
    }
}
