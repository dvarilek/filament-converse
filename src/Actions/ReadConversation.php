<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Actions;

use Dvarilek\FilamentConverse\Events\ConversationRead;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Models\MessageRead;
use Illuminate\Support\Facades\DB;

class ReadConversation
{
    public function handle(ConversationParticipation $readBy, Conversation $conversation): void
    {
        $messageRead = DB::transaction(static fn () => $readBy->update([
            'last_read_at' => now()
        ]));

        broadcast(new ConversationRead($readBy, $conversation))->toOthers();
    }
}
