<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\ChatConversation::find($conversationId);
    if (!$conversation) {
        return false;
    }
    if ($user) {
        return $conversation->user_id == $user->id || auth()->guard('admin')->check();
    }
    $metadata = json_decode($conversation->metadata, true);
    return $metadata['temp_user_id'] ?? false;
});
