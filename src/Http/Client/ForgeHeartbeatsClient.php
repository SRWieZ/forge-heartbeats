<?php

namespace SRWieZ\ForgeHeartbeats\Http\Client;

use Composer\InstalledVersions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;

class ForgeHeartbeatsClient
{
    public function __construct(
        protected string $apiToken,
        protected string $organization,
        protected int $serverId,
        protected int $siteId,
    ) {}

    /**
     * @return array<Heartbeat>
     *
     * @throws RequestException
     */
    public function listHeartbeats(): array
    {
        $data = $this->request()->get('/')->throw()->json('data', []);

        return array_map(fn (array $heartbeat) => Heartbeat::fromArray($heartbeat), $data);
    }

    /**
     * @throws RequestException
     */
    public function getHeartbeat(int $heartbeatId): Heartbeat
    {
        return Heartbeat::fromArray($this->request()->get('/' . $heartbeatId)->throw()->json('data'));
    }

    /**
     * @throws RequestException
     */
    public function createHeartbeat(string $name, int $gracePeriod, int $frequency, ?string $customFrequency = null): Heartbeat
    {
        $body = $this->heartbeatBody($name, $gracePeriod, $frequency, $customFrequency);

        return Heartbeat::fromArray($this->request()->post('/', $body)->throw()->json('data'));
    }

    /**
     * @throws RequestException
     */
    public function updateHeartbeat(int $heartbeatId, string $name, int $gracePeriod, int $frequency, ?string $customFrequency = null): Heartbeat
    {
        $body = $this->heartbeatBody($name, $gracePeriod, $frequency, $customFrequency);

        return Heartbeat::fromArray($this->request()->put('/' . $heartbeatId, $body)->throw()->json('data'));
    }

    /**
     * @throws RequestException
     */
    public function deleteHeartbeat(int $heartbeatId): void
    {
        $this->request()->delete('/' . $heartbeatId)->throw();
    }

    protected function request(): PendingRequest
    {
        $version = InstalledVersions::getVersion('srwiez/forge-heartbeats') ?? 'dev-main';

        return Http::baseUrl(sprintf(
            'https://forge.laravel.com/api/orgs/%s/servers/%d/sites/%d/heartbeats',
            $this->organization,
            $this->serverId,
            $this->siteId
        ))
            ->withToken($this->apiToken)
            ->acceptJson()
            ->asJson()
            ->withUserAgent('ForgeHeartbeats/' . $version . ' (srwiez/forge-heartbeats composer package)')
            ->timeout(config('forge-heartbeats.api.timeout', 30));
    }

    protected function heartbeatBody(string $name, int $gracePeriod, int $frequency, ?string $customFrequency): array
    {
        $body = [
            'name' => $name,
            'grace_period' => $gracePeriod,
            'frequency' => $frequency,
        ];

        if ($customFrequency) {
            $body['custom_frequency'] = $customFrequency;
        }

        return $body;
    }
}
