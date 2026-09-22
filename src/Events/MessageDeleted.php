<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Events;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        private readonly string $messageKey,
        private readonly string $messageAuthorKey,
        private readonly Conversation $conversation
    ) {}

    /**
     * @return class-string<PrivateChannel>
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('filament-converse.conversation.' . $this->conversation->getKey());
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->messageKey,
                'authorId' => $this->messageAuthorKey,
            ],
        ];
    }
}
