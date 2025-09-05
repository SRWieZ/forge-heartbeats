<?php

namespace SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;

class CreateHeartbeatRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $name,
        protected int $gracePeriod,
        protected int $frequency,
        protected ?string $customFrequency = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/';
    }

    protected function defaultBody(): array
    {
        $body = [
            'name' => $this->name,
            'grace_period' => $this->gracePeriod,
            'frequency' => $this->frequency,
        ];

        if ($this->customFrequency) {
            $body['custom_frequency'] = $this->customFrequency;
        }

        return $body;
    }

    public function createDtoFromResponse(Response $response): Heartbeat
    {
        return Heartbeat::fromArray($response->json('data'));
    }
}
