<?php

namespace SRWieZ\ForgeHeartbeats\Http\Client;

use Composer\InstalledVersions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
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
     * Upper bound on pages read by listHeartbeats(), in case Forge keeps returning a next cursor.
     */
    protected const MAX_PAGES = 100;

    /**
     * List every heartbeat of the site, following Forge's cursor pagination.
     *
     * @return array<Heartbeat>
     *
     * @throws RequestException
     * @throws RuntimeException when pagination doesn't end
     */
    public function listHeartbeats(): array
    {
        $heartbeats = [];
        $seenCursors = [];
        $cursor = null;

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = $this->request()
                ->get('/', $cursor === null ? [] : ['page[cursor]' => $cursor])
                ->throw();

            foreach ($response->json('data', []) as $heartbeat) {
                $heartbeats[] = Heartbeat::fromArray($heartbeat);
            }

            $cursor = $response->json('meta.next_cursor');

            if (empty($cursor)) {
                return $heartbeats;
            }

            if (isset($seenCursors[$cursor])) {
                throw new RuntimeException('Forge returned the same pagination cursor twice while listing heartbeats.');
            }

            $seenCursors[$cursor] = true;
        }

        // A partial list would make sync create duplicates and miss orphans, so fail instead.
        throw new RuntimeException('Forge returned more than ' . self::MAX_PAGES . ' pages of heartbeats.');
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
        $version = InstalledVersions::getPrettyVersion('srwiez/forge-heartbeats') ?? 'dev-main';

        $apiUrl = rtrim(config('forge-heartbeats.api.base_url') ?: 'https://forge.laravel.com/api', '/');

        return Http::baseUrl(sprintf(
            '%s/orgs/%s/servers/%d/sites/%d/heartbeats',
            $apiUrl,
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
