<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Events\MessageDeleted;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;

class DeleteMessage
{
    public function handle(Message $message, Conversation $conversation): bool
    {
        $messageKey = $message->getKey();
        $messageAuthorKey = $message->author_id;

        $result = $message->delete();

        broadcast(new MessageDeleted($messageKey, $messageAuthorKey, $conversation))->toOthers();

        return $result;
    }
}
