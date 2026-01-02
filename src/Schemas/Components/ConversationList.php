<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use BackedEnum;
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
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Component as LivewireComponent;

class ConversationList extends Component
{
    use Concerns\BelongsToConversationSchema;
    use Concerns\HasEmptyState;
    use Concerns\HasExtraConversationAttributes;
    use Concerns\HasSearch;
    use HasExtraAttributes;
    use HasKey;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const CREATE_CONVERSATION_NESTED_ACTIONS_KEY = 'create_conversation_nested_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-list';

    protected string | Htmlable | Closure | null $heading = null;

    protected string | Htmlable | Closure | null $description = null;

    protected int | Closure | null $defaultLoadedConversationsCount = 10;

    protected int | Closure | null $conversationsLoadedPerPage = 10;

    protected ?Closure $getLatestMessageUsing = null;

    protected ?Closure $getLatestMessageDateTimeUsing = null;

    protected ?Closure $getLatestMessageContentUsing = null;

    protected string | Htmlable | Closure | null $latestMessageEmptyContent = null;

    protected string | array | Closure | null $unreadMessagesBadgeColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $unreadMessagesBadgeIcon = null;

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

        $this->getLatestMessageDateTimeUsing(static function (Message $latestMessage): string {
            return $latestMessage->created_at->shortAbsoluteDiffForHumans();
        });

        $this->getLatestMessageUsing(static function (Conversation $conversation): ?Message {
            return $conversation
                ->participations
                ->pluck('latestMessage')
                ->filter()
                ->sortByDesc('created_at')
                ->first();
        });

        $this->getLatestMessageContentUsing(static function (Conversation $conversation, Message $latestMessage): string {
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

        $this->childComponents(fn () => [
            $this->getCreateConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getCreateDirectConversationAction(),
            $this->getCreateGroupConversationAction(),
        ], static::CREATE_CONVERSATION_NESTED_ACTIONS_KEY);
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

    public function defaultLoadedConversationsCount(int | Closure | null $count): static
    {
        $this->defaultLoadedConversationsCount = $count;

        return $this;
    }

    public function conversationsLoadedPerPage(int | Closure | null $count): static
    {
        $this->conversationsLoadedPerPage = $count;

        return $this;
    }

    public function getLatestMessageUsing(?Closure $callback = null): static
    {
        $this->getLatestMessageUsing = $callback;

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

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function unreadMessagesBadgeColor(string | array | Closure | null $color): static
    {
        $this->unreadMessagesBadgeColor = $color;

        return $this;
    }

    public function unreadMessagesBadgeIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->unreadMessagesBadgeIcon = filled($icon) ? $icon : false;

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

    public function getDefaultLoadedConversationsCount(): int
    {
        return $this->evaluate($this->defaultLoadedConversationsCount) ?? 15;
    }

    public function getConversationsLoadedPerPage(): int
    {
        return $this->evaluate($this->conversationsLoadedPerPage) ?? 15;
    }

    public function getLatestMessage(Conversation $conversation): ?Message
    {
        return $this->evaluate($this->getLatestMessageUsing, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getLatestMessageDateTime(Message $latestMessage, Conversation $conversation): ?string
    {
        return $this->evaluate($this->getLatestMessageDateTimeUsing, [
            'latestMessage' => $latestMessage,
            'conversation' => $conversation,
        ], [
            Message::class => $latestMessage,
            Conversation::class => $conversation,
        ]);
    }

    public function getLatestMessageContent(Message $latestMessage, Conversation $conversation): ?string
    {
        return $this->evaluate($this->getLatestMessageContentUsing, [
            'latestMessage' => $latestMessage,
            'conversation' => $conversation,
        ], [
            Message::class => $latestMessage,
            Conversation::class => $conversation,
        ]);
    }

    public function getLatestMessageEmptyContent(Conversation $conversation): string | Htmlable | null
    {
        return $this->evaluate($this->latestMessageEmptyContent, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getUnreadMessagesBadgeColor(Message $latestMessage, Conversation $conversation): string | array | null
    {
        return $this->evaluate($this->unreadMessagesBadgeColor, [
            'latestMessage' => $latestMessage,
            'conversation' => $conversation,
        ], [
            Message::class => $latestMessage,
            Conversation::class => $conversation,
        ]);
    }

    public function getUnreadMessagesBadgeIcon(Message $latestMessage, Conversation $conversation): string | BackedEnum | Htmlable | null
    {
        return $this->evaluate($this->unreadMessagesBadgeIcon, [
            'conversation' => $conversation,
        ], [
            Message::class => $latestMessage,
            Conversation::class => $conversation,
        ]);
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
