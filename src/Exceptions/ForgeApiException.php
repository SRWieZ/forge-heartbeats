<?php

namespace SRWieZ\ForgeHeartbeats\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class ForgeApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?ResponseInterface $response = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(ResponseInterface $response, ?string $customMessage = null): self
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $message = $customMessage ?? '';

        if ($body) {
            $data = json_decode($body, true);
            if (isset($data['message'])) {
                $message .= ': ' . $data['message'];
            }
        } else {
            $message .= ': Forge API request failed with status ' . $statusCode;
        }

        return new self($message, $statusCode, $response);
    }

    public function getResponseBody(): ?string
    {
        return $this->response?->getBody()->getContents();
    }
}
