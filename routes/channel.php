<?php

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('filament-converse.conversation.{conversation}', function ($user, string $conversationKey) {

    dd($user, $conversationKey);

    return Conversation::query()->whereKey($conversationKey)
        ->whereHas('participations', fn (Builder $query) => $query->where('participant_id', $user->getKey()))
        ->exists();
});
