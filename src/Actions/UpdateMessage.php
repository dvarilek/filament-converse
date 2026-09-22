<?php

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Events\MessageUpdated;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;

class UpdateMessage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Message $message, Conversation $conversation, array $attributes): bool
    {
        $result = $message->update([
            'content' => $attributes['content'] ?? null,
            'attachments' => $attributes['attachments'] ?? [],
            'attachment_file_names' => $attributes['attachment_file_names'] ?? [],
        ]);

        broadcast(new MessageUpdated($message, $conversation))->toOthers();

        return $result;
    }
}
