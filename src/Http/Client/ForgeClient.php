<?php

namespace SRWieZ\ForgeHeartbeats\Http\Client;

use Composer\InstalledVersions;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;
use SRWieZ\ForgeHeartbeats\Http\Client\Exceptions\ForgeApiException;
use SRWieZ\ForgeHeartbeats\Http\Client\Exceptions\InvalidConfigException;

class ForgeClient implements ForgeClientInterface
{
    private Client $client;

    private string $baseUrl;

    private ?string $apiToken;

    private ?string $organization;

    private ?int $serverId;

    private ?int $siteId;

    public function __construct()
    {
        $this->apiToken = config('forge-heartbeats.api_token');
        $this->organization = config('forge-heartbeats.organization');
        $this->serverId = config('forge-heartbeats.server_id');
        $this->siteId = config('forge-heartbeats.site_id');
        $this->baseUrl = config('forge-heartbeats.api.base_url', 'https://forge.laravel.com/api/');

        $this->validateConfig();

        // Get the current package version
        $version = InstalledVersions::getVersion('srwiez/forge-heartbeats') ?? 'dev-main';

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('forge-heartbeats.api.timeout', 30),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'ForgeHeartbeats/' . $version . ' (srwiez/forge-heartbeats composer package)',
            ],
        ]);
    }

    public function listHeartbeats(): array
    {
        $url = "orgs/{$this->organization}/servers/{$this->serverId}/sites/{$this->siteId}/heartbeats";

        try {
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return array_map(
                fn (array $heartbeatData) => Heartbeat::fromArray($heartbeatData),
                $data['data'] ?? []
            );
        } catch (ClientException|RequestException $e) {
            throw ForgeApiException::fromResponse($e->getResponse(), 'Failed to list heartbeats');
        }
    }

    public function createHeartbeat(string $name, int $gracePeriod, int $frequency, ?string $customFrequency = null): Heartbeat
    {
        $url = "orgs/{$this->organization}/servers/{$this->serverId}/sites/{$this->siteId}/heartbeats";

        $payload = [
            'name' => $name,
            'grace_period' => $gracePeriod,
            'frequency' => $frequency,
        ];

        if ($customFrequency) {
            $payload['custom_frequency'] = $customFrequency;
        }

        try {
            $response = $this->client->post($url, [
                RequestOptions::JSON => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return Heartbeat::fromArray($data['data']);
        } catch (ClientException|RequestException $e) {
            throw ForgeApiException::fromResponse($e->getResponse(), "Failed to create heartbeat '{$name}'");
        }
    }

    public function getHeartbeat(int $heartbeatId): Heartbeat
    {
        $url = "orgs/{$this->organization}/servers/{$this->serverId}/sites/{$this->siteId}/heartbeats/{$heartbeatId}";

        try {
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return Heartbeat::fromArray($data['data']);
        } catch (ClientException|RequestException $e) {
            throw ForgeApiException::fromResponse($e->getResponse(), "Failed to get heartbeat {$heartbeatId}");
        }
    }

    public function updateHeartbeat(int $heartbeatId, string $name, int $gracePeriod, int $frequency, ?string $customFrequency = null): Heartbeat
    {
        $url = "orgs/{$this->organization}/servers/{$this->serverId}/sites/{$this->siteId}/heartbeats/{$heartbeatId}";

        $payload = [
            'name' => $name,
            'grace_period' => $gracePeriod,
            'frequency' => $frequency,
        ];

        if ($customFrequency) {
            $payload['custom_frequency'] = $customFrequency;
        }

        try {
            $response = $this->client->put($url, [
                RequestOptions::JSON => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return Heartbeat::fromArray($data['data']);
        } catch (ClientException|RequestException $e) {
            throw ForgeApiException::fromResponse($e->getResponse(), "Failed to update heartbeat {$heartbeatId}");
        }
    }

    public function deleteHeartbeat(int $heartbeatId): bool
    {
        $url = "orgs/{$this->organization}/servers/{$this->serverId}/sites/{$this->siteId}/heartbeats/{$heartbeatId}";

        try {
            $response = $this->client->delete($url);

            return $response->getStatusCode() === 204;
        } catch (ClientException|RequestException $e) {
            throw ForgeApiException::fromResponse($e->getResponse(), "Failed to delete heartbeat {$heartbeatId}");
        }
    }

    public function pingHeartbeat(string $pingUrl): bool
    {
        try {
            $response = $this->client->get($pingUrl);

            return in_array($response->getStatusCode(), [200, 201, 202, 204]);
        } catch (ClientException|RequestException $e) {
            // Heartbeat pings can fail silently, we don't want to break the application
            return false;
        }
    }

    private function validateConfig(): void
    {
        if (empty($this->apiToken)) {
            throw InvalidConfigException::missingApiToken();
        }

        if (empty($this->organization)) {
            throw InvalidConfigException::missingOrganization();
        }

        if (empty($this->serverId)) {
            throw InvalidConfigException::missingServerId();
        }

        if (empty($this->siteId)) {
            throw InvalidConfigException::missingSiteId();
        }
    }
}
