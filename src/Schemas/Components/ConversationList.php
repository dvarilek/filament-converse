<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\Create\CreateDirectConversationAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\Create\CreateGroupConversationAction;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Component as LivewireComponent;

class ConversationList extends Component
{
    use Concerns\HasConversations;
    use Concerns\HasEmptyState;
    use Concerns\HasSearch;

    const HEADER_ACTIONS_KEY = 'header_actions';

    const CREATE_CONVERSATION_NESTED_ACTIONS_KEY = 'create_conversation_nested_actions';

    /**
     * @var view-string
     */
    protected string $view = 'filament-converse::conversation-list';

    protected string | Htmlable | Closure | null $heading = null;

    protected string | Htmlable | Closure | null $description = null;

    protected bool | Closure $shouldConversationListOverflow = false;

    protected ?Closure $modifyCreateConversationActionUsing = null;

    protected ?Closure $modifyCreateDirectConversationActionUsing = null;

    protected ?Closure $modifyCreateGroupConversationActionUsing = null;

    public function __construct(string | Closure | null $heading)
    {
        $this->heading($heading);
    }

    public static function make(string | Closure | null $heading = null)
    {
        $static = app(static::class, ['heading' => $heading]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchPlaceholder(__('filament-converse::conversation-list.search.placeholder'));

        $this->emptyStateDescription(static function () {
            if (! auth()->user()->participatesInAnyConversation()) {
                return __('filament-converse::conversation-list.empty-state.description');
            }
        });

        $this->childComponents(fn () => [
            $this->getCreateConversationAction(),
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getCreateDirectConversationAction(),
            $this->getCreateGroupConversationAction(),
        ], static::CREATE_CONVERSATION_NESTED_ACTIONS_KEY);

        $this->getConversationNameUsing(static function (Conversation $conversation) {
            return $conversation->getName();
        });

        $this->getConversationImageUsing(static function (Conversation $conversation) {
            return $conversation->image;
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

    public function conversationListOverflow(bool | Closure $condition = true): static
    {
        $this->shouldConversationListOverflow = $condition;

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

    public function shouldConversationListOverflow(): bool
    {
        return (bool) $this->evaluate($this->shouldConversationListOverflow);
    }

    public function getLivewire(): LivewireComponent & HasSchemas & HasActions & HasConversationList
    {
        return parent::getLivewire();
    }

    protected function getCreateConversationAction(): Action
    {
        $action = Action::make('createConversation')
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
                Action::class => $action,
            ]) ?? $action;
        }

        return $action;
    }
}
