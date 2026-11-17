<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Concerns;

use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\CancelReplyToMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ReplyToMessageAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Closure;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Text;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;

trait CanReplyToMessage
{
    protected ?Closure $getRepliedToMessageAuthorNameUsing = null;

    protected ?Closure $getRepliedToMessageAuthorAvatarUsing = null;

    protected ?Closure $getRepliedToMessageContentUsing = null;

    protected string | Htmlable | Closure | null $getRepliedToMessageToolbarUsing = null;

    protected ?Closure $modifyReplyToMessageActionUsing = null;

    protected ?Closure $getReplyingToMessageTextUsing = null;

    protected ?Closure $modifyReplyingToMessageTextComponent = null;

    protected ?Closure $modifyCancelReplyToMessageActionUsing = null;

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

    public function getReplyingToMessageTextUsing(?Closure $callback = null): static
    {
        $this->getReplyingToMessageTextUsing = $callback;

        return $this;
    }

    public function modifyReplyingToMessageTextComponent(?Closure $callback = null): static
    {
        $this->modifyReplyingToMessageTextComponent = $callback;

        return $this;
    }

    public function modifyCancelReplyToMessageActionUsing(?Closure $callback = null): static
    {
        $this->modifyCancelReplyToMessageActionUsing = $callback;

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

    public function getReplyToMessageBanner(): ?Component
    {
        $replyingToMessageKey = $this->getLivewire()->replyingToMessageKey;

        if (! $replyingToMessageKey) {
            return null;
        }

        $textComponent = Text::make(fn () => $this->evaluate($this->getReplyingToMessageTextUsing))
            ->extraAttributes([
                'x-on:click' => 'navigateToMessage(' . Js::from($replyingToMessageKey) . ')',
            ]);
        $action = CancelReplyToMessageAction::make();

        if ($this->modifyReplyingToMessageTextComponent) {
            $textComponent = $this->evaluate($this->modifyReplyingToMessageTextComponent, [
                'component' => $textComponent,
            ], [
                Text::class => $textComponent,
            ]) ?? $textComponent;
        }

        if ($this->modifyCancelReplyToMessageActionUsing) {
            $action = $this->evaluate($this->modifyCancelReplyToMessageActionUsing, [
                'action' => $action,
            ], [
                CancelReplyToMessageAction::class => $action,
            ]) ?? $action;
        }

        return Flex::make([
            $textComponent,
            $action
        ]);
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
