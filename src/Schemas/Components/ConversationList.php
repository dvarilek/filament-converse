<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList\CreateDirectConversationAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList\CreateGroupConversationAction;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\HtmlString;
use Livewire\Component as LivewireComponent;

class ConversationList extends Component
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use Concerns\HasSearch;
    use HasKey;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const CREATE_CONVERSATION_NESTED_ACTIONS_KEY = 'create_conversation_nested_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-list';

    protected string | Htmlable | Closure | null $heading = null;

    protected string | Htmlable | Closure | null $description = null;

    protected bool | Closure $hasHeadingBadge = true;

    protected int | string | Closure | null $headingBadgeState = null;

    protected string | array | Closure | null $headingBadgeColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $headingBadgeIcon = null;

    protected ?Closure $getLatestMessageDateTimeUsing = null;

    protected ?Closure $getLatestMessageContentUsing = null;

    protected string | Htmlable | Closure | null $latestMessageEmptyContent = null;

    protected ?Closure $modifyCreateConversationActionUsing = null;

    protected ?Closure $modifyCreateDirectConversationActionUsing = null;

    protected ?Closure $modifyCreateGroupConversationActionUsing = null;

    final public function __construct(string | Htmlable | Closure | null $heading)
    {
        $this->heading($heading);
    }

    public static function make(string | Htmlable | Closure | null $heading = null)
    {
        $static = app(static::class, ['heading' => $heading]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key('conversation-list');

        $this->searchPlaceholder(__('filament-converse::conversation-list.search.placeholder'));

        $this->emptyStateHeading(__('filament-converse::conversation-list.empty-state.heading'));

        $this->latestMessageEmptyContent(__('filament-converse::conversation-list.latest-message.empty-state'));

        $this->emptyStateDescription(static function () {
            if (! auth()->user()->participatesInAnyConversation()) {
                return __('filament-converse::conversation-list.empty-state.description');
            }
        });

        $this->headingBadgeState(static function (HasConversationSchema $livewire) {
            return $livewire->conversations->count();
        });

        $this->childComponents(fn () => [
            $this->getCreateConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getCreateDirectConversationAction(),
            $this->getCreateGroupConversationAction(),
        ], static::CREATE_CONVERSATION_NESTED_ACTIONS_KEY);

        $this->getLatestMessageDateTimeUsing(static function (Message $latestMessage) {
            return $latestMessage->created_at->shortAbsoluteDiffForHumans();
        });

        $this->getLatestMessageContentUsing(static function (Conversation $conversation, Message $latestMessage, HasConversationSchema $livewire) {
            $participantWithLatestMessage = $conversation
                ->participations
                ->firstWhere((new ConversationParticipation)->getKeyName(), $latestMessage->author_id)
                ->participant;

            $messagePrefix = $participantWithLatestMessage->getKey() === auth()->id()
                ? __('filament-converse::conversation-list.latest-message.current-user')
                : $participantWithLatestMessage->getAttributeValue($participantWithLatestMessage::getFilamentNameAttribute());

            $message = match (true) {
                filled($content = $latestMessage->content) => $content,
                filled($attachments = $latestMessage->attachments) => trans_choice('filament-converse::conversation-list.latest-message.only-attachments', count($attachments), ['count' => count($attachments)]),
                default => null,
            };

            return $messagePrefix . ': ' . $message;
        });
    }

    public function heading(string | Htmlable | Closure | null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(string | Htmlable | Closure | null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function headingBadge(bool | Closure $condition = true): static
    {
        $this->hasHeadingBadge = $hasBadge;

        return $this;
    }

    public function headingBadgeState(int | string | Closure | null $state): static
    {
        $this->headingBadgeState = $state;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function headingBadgeColor(string | array | Closure | null $color): static
    {
        $this->headingBadgeColor = $color;

        return $this;
    }

    public function headingBadgeIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->headingBadgeIcon = filled($icon) ? $icon : false;

        return $this;
    }

    public function getLatestMessageDateTimeUsing(?Closure $callback = null): static
    {
        $this->getLatestMessageDateTimeUsing = $callback;

        return $this;
    }

    public function getLatestMessageContentUsing(?Closure $callback = null): static
    {
        $this->getLatestMessageContentUsing = $callback;

        return $this;
    }

    public function latestMessageEmptyContent(string | Htmlable | Closure | null $content): static
    {
        $this->latestMessageEmptyContent = $content;

        return $this;
    }

    public function createConversationAction(?Closure $callback): static
    {
        $this->modifyCreateConversationActionUsing = $callback;

        return $this;
    }

    public function createDirectConversationAction(?Closure $callback): static
    {
        $this->modifyCreateDirectConversationActionUsing = $callback;

        return $this;
    }

    public function createGroupConversationAction(?Closure $callback): static
    {
        $this->modifyCreateGroupConversationActionUsing = $callback;

        return $this;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->evaluate($this->heading) ?? __('filament-converse::conversation-list.heading');
    }

    public function getDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->description);
    }

    public function hasHeadingBadge(): bool
    {
        return (bool) $this->evaluate($this->hasHeadingBadge);
    }

    public function getHeadingBadgeState(): int | string | null
    {
        return $this->evaluate($this->headingBadgeState);
    }

    /**
     * @return string | array<string> | null
     */
    public function getHeadingBadgeColor(): string | array | null
    {
        return $this->evaluate($this->headingBadgeColor);
    }

    public function getHeadingBadgeIcon(): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->headingBadgeIcon);

        // https://github.com/filamentphp/filament/pull/13512
        if ($icon instanceof Renderable) {
            return new HtmlString($icon->render());
        }

        if ($icon === false) {
            return null;
        }

        return $icon;
    }

    public function getLatestMessageDateTime(Conversation $conversation, Message $latestMessage): ?string
    {
        return $this->evaluate($this->getLatestMessageDateTimeUsing, [
            'conversation' => $conversation,
            'latestMessage' => $latestMessage,
            'message' => $latestMessage,
        ], [
            Conversation::class => $conversation,
            Message::class => $latestMessage,
        ]);
    }

    public function getLatestMessageContent(Conversation $conversation, Message $latestMessage): ?string
    {
        return $this->evaluate($this->getLatestMessageContentUsing, [
            'conversation' => $conversation,
            'latestMessage' => $latestMessage,
            'message' => $latestMessage,
        ], [
            Conversation::class => $conversation,
            Message::class => $latestMessage,
        ]);
    }

    public function getLatestMessageEmptyContent(): string | Htmlable | null
    {
        return $this->evaluate($this->latestMessageEmptyContent);
    }

    protected function getCreateConversationAction(): Action
    {
        $action = Action::make('createConversation')
            ->modalIcon(Heroicon::Plus)
            ->label(__('filament-converse::conversation-list.actions.create.label'))
            ->icon(Heroicon::Plus)
            ->modalAlignment(Alignment::Center)
            ->modalWidth(Width::Medium)
            ->modalHeading(__('filament-converse::conversation-list.actions.create.modal-heading'))
            ->modalDescription(__('filament-converse::conversation-list.actions.create.modal-description'))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalFooterActions($this->getChildCOmponents(static::CREATE_CONVERSATION_NESTED_ACTIONS_KEY));

        if ($this->modifyCreateConversationActionUsing) {
            $action = $this->evaluate($this->modifyCreateConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getCreateDirectConversationAction(): Action
    {
        $action = CreateDirectConversationAction::make();

        if ($this->modifyCreateDirectConversationActionUsing) {
            $action = $this->evaluate($this->modifyCreateDirectConversationActionUsing, [
                'action' => $action,
            ], [
                CreateDirectConversationAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    protected function getCreateGroupConversationAction(): Action
    {
        $action = CreateGroupConversationAction::make();

        if ($this->modifyCreateGroupConversationActionUsing) {
            $action = $this->evaluate($this->modifyCreateGroupConversationActionUsing, [
                'action' => $action,
            ], [
                CreateGroupConversationAction::class => $action,
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getLivewire(): LivewireComponent & HasSchemas & HasActions & HasConversationSchema
    {
        return parent::getLivewire();
    }
}
