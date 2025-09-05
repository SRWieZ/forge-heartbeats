<?php

namespace SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;

class ListHeartbeatsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/';
    }

    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->json('data', []);

        return array_map(
            fn (array $heartbeatData) => Heartbeat::fromArray($heartbeatData),
            $data
        );
    }
}
