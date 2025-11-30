<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Events;

use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        private readonly Message $message,
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
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->getKey(),
                'authorId' => $this->message->author_id,
            ],
        ];
    }
}
