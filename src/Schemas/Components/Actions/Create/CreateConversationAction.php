<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\Create;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class CreateConversationAction extends Action
{
    protected ?Closure $modifyParticipantSelectComponentUsing = null;

    protected ?Closure $modifyConversationCreateNotificationUsing = null;

    public function participantSelectComponent(?Closure $callback): static
    {
        $this->modifyParticipantSelectComponentUsing = $callback;

        return $this;
    }

    public function conversationCreatedNotification(?Closure $callback): static
    {
        $this->modifyConversationCreateNotificationUsing = $callback;

        return $this;
    }

    protected function getConversationCreatedNotification(): ?Notification
    {
        $notification = Notification::make('conversationCreated')
            ->success()
            ->title(__('filament-converse::conversation-list.actions.notifications.conversation-created-title'));

        if ($this->modifyConversationCreateNotificationUsing) {
            $notification = $this->evaluate($this->modifyConversationCreateNotificationUsing, [
                'notification' => $notification,
            ], [
                Notification::class => $notification,
            ]);
        }

        return $notification;
    }

    protected function generateConversationActionParticipantOption(string $name, Authenticatable & Model $user): string
    {
        $escapedName = e($name);
        $avatarUrl = filament()->getUserAvatarUrl((new $user)->setAttribute($user::getFilamentNameAttribute(), $name));

        return "
            <div style='display:flex;align-items:center;gap:0.5rem'>
                <img class='fi-avatar fi-circular sm' src='{$avatarUrl}' alt='{$escapedName}' style='padding:2px'>
                <span>{$escapedName}</span>
            </div>
        ";
    }
}
