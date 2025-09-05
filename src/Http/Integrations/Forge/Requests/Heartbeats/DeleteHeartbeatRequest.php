<?php

namespace SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteHeartbeatRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected int $heartbeatId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . $this->heartbeatId;
    }
}
