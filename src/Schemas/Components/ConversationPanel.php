<?php

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Schemas\Components\Concerns\BelongsToLivewire;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasKey;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Component as LivewireComponent;

class ConversationPanel extends Component
{
    use BelongsToLivewire;
    use HasKey;

    protected ?Closure $modifyConversationListUsing = null;

    protected ?Closure $modifyConversationThreadUsing = null;

    protected ?Closure $sortConversationsUsing = null;

    protected bool | Closure | null $persistsActiveConversationInSession = true;

    protected ?Closure $getDefaultActiveConversationUsing = null;

    protected ?Closure $getConversationNameUsing = null;

    protected ?Closure $getConversationImageUsing = null;

    protected string $view = 'filament-converse::conversation-panel';

    final public function __construct(LivewireComponent & HasSchemas & HasConversationList $livewire)
    {
        $this->livewire($livewire);
    }

    public static function make(LivewireComponent & HasSchemas & HasConversationList $livewire): static
    {
        $static = app(static::class, ['livewire' => $livewire]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columns(3);

        $this->schema(fn () => [
            $this->getConversationList(),
            $this->getConversationThread(),
        ]);

        $this->sortConversationsUsing(static function (Collection $conversations) {
            return $conversations->sortByDesc([
                static fn (Conversation $conversation) => $conversation
                    ->participations
                    ->pluck('latestMessage')
                    ->filter()
                    ->max('created_at')?->timestamp ?? 0,
                static fn (Conversation $conversation) => $conversation
                    ->created_at
                    ->timestamp,
            ]);
        });

        $this->getDefaultActiveConversationUsing(static function (Collection $conversations) {
            return $conversations->first();
        });

        $this->getConversationNameUsing(static function (Conversation $conversation) {
            return $conversation->getName();
        });

        $this->getConversationImageUsing(static function (Conversation $conversation) {
            return $conversation->image;
        });
    }

    public function conversationList(?Closure $callback): static
    {
        $this->modifyConversationListUsing = $callback;

        return $this;
    }

    public function conversationThread(?Closure $callback): static
    {
        $this->modifyConversationThreadUsing = $callback;

        return $this;
    }

    protected function sortConversationsUsing(Closure $callback): static
    {
        $this->sortConversationsUsing = $callback;

        return $this;
    }

    public function persistActiveConversationInSession(bool | Closure | null $condition = true): static
    {
        $this->persistsActiveConversationInSession = $condition;

        return $this;
    }

    public function getDefaultActiveConversationUsing(?Closure $callback = null): static
    {
        $this->getDefaultActiveConversationUsing = $callback;

        return $this;
    }

    public function getConversationNameUsing(Closure $callback): static
    {
        $this->getConversationNameUsing = $callback;

        return $this;
    }

    public function getConversationImageUsing(?Closure $callback = null): static
    {
        $this->getConversationImageUsing = $callback;

        return $this;
    }

    public function getConversationList(): ConversationList
    {
        $component = ConversationList::make()
            ->columnSpan(1);

        if ($this->modifyConversationListUsing) {
            $component = $this->evaluate($this->modifyConversationListUsing, [
                'component' => $component,
            ], [
                ConversationList::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function getConversationThread(): ConversationThread
    {
        $component = ConversationThread::make()
            ->columnSpan(2);

        if ($this->modifyConversationThreadUsing) {
            $component = $this->evaluate($this->modifyConversationThreadUsing, [
                'component' => $component,
            ], [
                ConversationThread::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    public function shouldPersistActiveConversationInSession(): bool
    {
        return (bool) $this->evaluate($this->persistsActiveConversationInSession);
    }

    public function getConversationName(Conversation $conversation): string | Htmlable | null
    {
        return $this->evaluate($this->getConversationNameUsing, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function getConversationImage(Conversation $conversation): ?string
    {
        return $this->evaluate($this->getConversationImageUsing, [
            'conversation' => $conversation,
        ], [
            Conversation::class => $conversation,
        ]);
    }

    public function hasConversationImageClosure(): bool
    {
        return $this->getConversationImageUsing !== null;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        $conversations = $this->getLivewire()->conversations;

        if ($this->sortConversationsUsing) {
            $conversations = $this->evaluate($this->sortConversationsUsing, [
                'conversations' => $conversations,
            ], [
                Collection::class => $conversations,
            ]) ?? $conversations;
        }

        return $conversations;
    }

    public function getActiveConversation(): ?Conversation
    {
        return $this->getLivewire()->getActiveConversation();
    }

    public function getDefaultActiveConversation(): ?Conversation
    {
        $conversations = $this->getConversations();

        return $this->evaluate($this->getDefaultActiveConversationUsing, [
            'conversations' => $conversations,
        ], [
            Collection::class => $conversations,
        ]);
    }
}
