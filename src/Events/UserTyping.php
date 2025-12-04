<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Events;

use Dvarilek\FilamentConverse\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly mixed $userId,
        public readonly string $userName,
        public readonly Conversation $conversation
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
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'name' => $this->userName,
            ],
        ];
    }
}
