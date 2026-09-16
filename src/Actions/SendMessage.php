<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Events\MessageSent;
use Dvarilek\FilamentConverse\Models\Conversation;
use Exception;
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
        $message = DB::transaction(static function () use ($author, $conversation, $attributes) {
            $replyToMessageKey = $attributes['reply_to_message_id'] ?? null;

            if ($replyToMessageKey !== null && $conversation->messages()->whereKey($replyToMessageKey)->doesntExist()) {
                throw new Exception("Message [$replyToMessageKey] does not exist in conversation [{$conversation->getKey()}].");
            }

            $author->update([
                'last_read_at' => now(),
            ]);

            return $author->messages()->create([
                'content' => $attributes['content'] ?? null,
                'attachments' => $attributes['attachments'] ?? [],
                'attachment_file_names' => $attributes['attachment_file_names'] ?? [],
                'reply_to_message_id' => $attributes['reply_to_message_id'] ?? null,
            ]);
        });

        broadcast(new MessageSent($message, $conversation))->toOthers();

        return $message;
    }
}
