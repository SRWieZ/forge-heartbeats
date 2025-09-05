<?php

namespace SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;

class PingHeartbeatRequest extends SoloRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $pingUrl,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->pingUrl;
    }
}
