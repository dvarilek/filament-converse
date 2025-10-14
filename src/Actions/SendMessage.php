<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Illuminate\Support\Facades\DB;

class SendMessage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ConversationParticipation $sender, Conversation $conversation, array $attributes): Message
    {
        /* @var Message */
        return DB::transaction(static fn () => $conversation->messages()->create([
            'content' => $attributes['content'] ?? null,
            'attachments' => $attributes['attachments'] ?? [],
            'reply_to_message_id' => $attributes['reply_to_message_id'] ?? null,
            'sender_id' => $sender->getKey(),
        ]));
    }
}
