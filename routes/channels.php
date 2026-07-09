<?php

use App\Models\GameServer;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Redis;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('server-status.{serverId}', function ($user, $serverId) {
    if (! $user) {
        return false;
    }

    $server = GameServer::find($serverId);
    if (! $server || ! $user->belongsToTeam($server->team)) {
        return false;
    }

    Redis::publish('ark:subscriptions', json_encode([
        'serverId' => $serverId,
        'action' => 'join',
        'userId' => "sidebar:{$user->id}",
    ]));

    return true;
});

Broadcast::channel('server.{serverId}', function ($user, $serverId) {
    if (! $user) {
        return null;
    }

    $server = GameServer::find($serverId);
    if (! $server || ! $user->belongsToTeam($server->team)) {
        return null;
    }

    Redis::publish('ark:subscriptions', json_encode([
        'serverId' => $serverId,
        'action' => 'join',
        'userId' => $user->id,
    ]));

    return ['id' => $user->id, 'name' => $user->name];
});
