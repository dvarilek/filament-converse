<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ReplyToMessageAction;
use Filament\Actions\Action;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

trait CanReplyToMessage
{
    protected ?Closure $getRepliedToMessageAuthorNameUsing = null;

    protected ?Closure $getRepliedToMessageAuthorAvatarUsing = null;

    protected ?Closure $getRepliedToMessageContentUsing = null;

    protected string | Htmlable | Closure | null $getRepliedToMessageToolbarUsing = null;

    protected ?Closure $modifyReplyToMessageActionUsing = null;

    protected bool | Closure $canReplyToMessage = true;

    public function getRepliedToMessageAuthorNameUsing(?Closure $callback = null): static
    {
        $this->getRepliedToMessageAuthorNameUsing = $callback;

        return $this;
    }

    public function getRepliedToMessageAuthorAvatarUsing(?Closure $callback = null): static
    {
        $this->getRepliedToMessageAuthorAvatarUsing = $callback;

        return $this;
    }

    public function getRepliedToMessageContentUsing(?Closure $callback = null): static
    {
        $this->getRepliedToMessageContentUsing = $callback;

        return $this;
    }

    public function getRepliedToMessageToolbarUsing(string | Htmlable | Closure | null $toolbar = null): static
    {
        $this->getRepliedToMessageToolbarUsing = $toolbar;

        return $this;
    }

    public function replyToMessageAction(?Closure $callback = null): static
    {
        $this->modifyReplyToMessageActionUsing = $callback;

        return $this;
    }

    public function replyToMessage(bool | CLosure $condition = true): static
    {
        $this->canReplyToMessage = $condition;

        return $this;
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getRepliedToMessageAuthorName(Message $message, Message $reply, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getRepliedToMessageAuthorNameUsing, [
            'message' => $message,
            'reply' => $reply,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getRepliedToMessageAuthorAvatar(Message $message, Message $reply, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getRepliedToMessageAuthorAvatarUsing, [
            'message' => $message,
            'reply' => $reply,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getRepliedToMessageContent(Message $message, Message $reply, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getRepliedToMessageContentUsing, [
            'message' => $message,
            'reply' => $reply,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     */
    public function getRepliedToMessageToolbar(Message $message, Message $reply, Authenticatable $messageAuthor, Collection $messages): string | Htmlable | null
    {
        return $this->evaluate($this->getRepliedToMessageToolbarUsing, [
            'message' => $message,
            'reply' => $reply,
            'messageAuthor' => $messageAuthor,
            'messages' => $messages,
        ], [
            Authenticatable::class => $messageAuthor,
            Collection::class => $messages,
        ]);
    }

    public function getReplyToMessageAction(): Action
    {
        $action = ReplyToMessageAction::make()
            ->visible(fn (?Message $message): bool => $message && $this->canReplyToMessage($message));

        if ($this->modifyReplyToMessageActionUsing) {
            $action = $this->evaluate($this->modifyReplyToMessageActionUsing, [
                'action' => $action,
            ], [
                ReplyToMessageAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function canReplyToMessage(Message $message): bool
    {
        return (bool) $this->evaluate($this->canReplyToMessage, [
            'message' => $message,
        ], [
            Message::class => $message,
        ]);
    }
}
