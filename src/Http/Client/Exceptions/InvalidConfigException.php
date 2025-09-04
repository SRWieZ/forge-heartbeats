<?php

namespace SRWieZ\ForgeHeartbeats\Http\Client\Exceptions;

use Exception;

class InvalidConfigException extends Exception
{
    public static function missingApiToken(): self
    {
        return new self('Forge API token is required. Set FORGE_API_TOKEN in your .env file.');
    }

    public static function missingOrganization(): self
    {
        return new self('Forge organization is required. Set FORGE_ORGANIZATION in your .env file.');
    }

    public static function missingServerId(): self
    {
        return new self('Forge server ID is required. Set FORGE_SERVER_ID in your .env file.');
    }

    public static function missingSiteId(): self
    {
        return new self('Forge site ID is required. Set FORGE_SITE_ID in your .env file.');
    }
}
