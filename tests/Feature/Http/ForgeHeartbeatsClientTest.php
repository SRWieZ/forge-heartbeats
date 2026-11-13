<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SRWieZ\ForgeHeartbeats\Http\Client\ForgeHeartbeatsClient;

function heartbeatData(int $id, string $name): array
{
    return [
        'id' => $id,
        'name' => $name,
        'status' => 'beating',
        'grace_period' => 5,
        'frequency' => 60,
        'custom_frequency' => null,
        'ping_url' => "https://forge.laravel.com/api/heartbeat/ping/test{$id}",
    ];
}

it('reads every page of heartbeats', function () {
    $pages = [
        'first' => ['data' => [heartbeatData(1, 'one'), heartbeatData(2, 'two')], 'meta' => ['next_cursor' => 'cursor-2']],
        'cursor-2' => ['data' => [heartbeatData(3, 'three')], 'meta' => ['next_cursor' => 'cursor-3']],
        'cursor-3' => ['data' => [heartbeatData(4, 'four')], 'meta' => ['next_cursor' => null]],
    ];

    Http::fake(fn (Request $request) => Http::response($pages[forgeCursor($request) ?? 'first']));

    $heartbeats = app(ForgeHeartbeatsClient::class)->listHeartbeats();

    expect(array_map(fn ($heartbeat) => $heartbeat->name, $heartbeats))->toBe(['one', 'two', 'three', 'four']);
    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => forgeCursor($request) === 'cursor-3');
});

it('stops when Forge returns a cursor it already returned', function () {
    Http::fake(fn () => Http::response(['data' => [heartbeatData(1, 'one')], 'meta' => ['next_cursor' => 'same-cursor']]));

    expect(fn () => app(ForgeHeartbeatsClient::class)->listHeartbeats())
        ->toThrow(RuntimeException::class, 'Forge returned the same pagination cursor twice');

    Http::assertSentCount(2);
});

it('stops after a maximum number of pages', function () {
    $page = 0;

    Http::fake(function () use (&$page) {
        $page++;

        return Http::response(['data' => [heartbeatData($page, "task-{$page}")], 'meta' => ['next_cursor' => "cursor-{$page}"]]);
    });

    expect(fn () => app(ForgeHeartbeatsClient::class)->listHeartbeats())
        ->toThrow(RuntimeException::class, 'more than 100 pages');

    Http::assertSentCount(100);
});
