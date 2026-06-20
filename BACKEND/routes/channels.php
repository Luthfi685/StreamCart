<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('live-stream.{sessionId}', function ($user, $sessionId) {
    // Return array of user data for PresenceChannel
    return ['id' => $user->id, 'name' => $user->name];
});
