<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Events\MessageSent;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Illuminate\Support\Facades\DB;

class SendMessage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ConversationParticipation $author, Conversation $conversation, array $attributes): Message
    {
        /* @var Message */
        $message = DB::transaction(static fn () => $author->messages()->create([
            'content' => $attributes['content'] ?? null,
            'attachments' => $attributes['attachments'] ?? [],
            'attachment_file_names' => $attributes['attachment_file_names'] ?? [],
            'reply_to_message_id' => $attributes['reply_to_message_id'] ?? null,
        ]));

        broadcast(new MessageSent($message, $conversation))->toOthers();

        return $message;
    }
}
