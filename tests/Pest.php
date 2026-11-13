<?php

use Illuminate\Http\Client\Request;
use SRWieZ\ForgeHeartbeats\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * The page[cursor] query parameter of a faked Forge API request.
 */
function forgeCursor(Request $request): ?string
{
    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

    return $query['page']['cursor'] ?? null;
}
