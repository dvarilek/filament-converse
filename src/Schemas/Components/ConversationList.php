<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use BackedEnum;
use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList\CreateConversationAction;
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
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
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

    protected int | Closure | null $defaultLoadedConversationsCount = 10;

    protected int | Closure | null $conversationsLoadedPerPage = 10;

    protected ?Closure $getLatestMessageUsing = null;

    protected ?Closure $getLatestMessageDateTimeUsing = null;

    protected ?Closure $getLatestMessageContentUsing = null;

    protected string | Htmlable | Closure | null $latestMessageEmptyContent = null;

    protected string | array | Closure | null $unreadMessagesBadgeColor = null;

    protected string | BackedEnum | Htmlable | Closure | false | null $unreadMessagesBadgeIcon = null;

    protected ?Closure $modifyCreateConversationActionUsing = null;

    protected View | Closure | null $conversationItemContent = null;

    protected View | Htmlable | Closure | null $aboveConversationItemContent = null;

    protected View | Htmlable | Closure | null $belowConversationItemContent = null;

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
        
        $this->emptyStateDescription(static function (): ?string {
            return ! auth()->user()->participatesInAnyConversation() ? __('filament-converse::conversation-list.empty-state.description') : null;
        });

        $this->headingBadgeState(static function (HasConversationSchema $livewire): int | string {
            $authenticatedUserKey = auth()->id();

            return $livewire->conversations->sum(static function (Conversation $conversation) use ($authenticatedUserKey) {
                return $conversation
                    ->participations
                    ->firstWhere('participant_id', $authenticatedUserKey)
                    ->unread_messages_count;
            });
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

    public function conversationItemContent(View | Closure | null $content): static
    {
        $this->conversationItemContent = $content;

        return $this;
    }

    public function aboveConversationItemContent(View | Htmlable | Closure | null $content): static
    {
        $this->aboveConversationItemContent = $content;

        return $this;
    }

    public function belowConversationItemContent(View | Htmlable | Closure | null $content): static
    {
        $this->belowConversationItemContent = $content;

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
        $action = CreateConversationAction::make();

        if ($this->modifyCreateConversationActionUsing) {
            $action = $this->evaluate($this->modifyCreateConversationActionUsing, [
                'action' => $action,
            ], [
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getConversationItemContent(Conversation $conversation): ?View
    {
        return $this->evaluate($this->conversationItemContent, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getAboveConversationItemContent(Conversation $conversation): View | Htmlable | null
    {
        return $this->evaluate($this->aboveConversationItemContent, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getBelowConversationItemContent(Conversation $conversation): View | Htmlable | null
    {
        return $this->evaluate($this->belowConversationItemContent, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getLivewire(): LivewireComponent & HasSchemas & HasActions & HasConversationSchema
    {
        return parent::getLivewire();
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'livewire' => [$this->getLivewire()],
            default => []
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            ConversationManager::class => [$this->getLivewire()],
            default => []
        };
    }
}
