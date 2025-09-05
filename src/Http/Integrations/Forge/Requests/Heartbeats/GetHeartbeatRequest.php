<?php

namespace SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;

class GetHeartbeatRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $heartbeatId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . $this->heartbeatId;
    }

    public function createDtoFromResponse(Response $response): Heartbeat
    {
        return Heartbeat::fromArray($response->json('data'));
    }
}
