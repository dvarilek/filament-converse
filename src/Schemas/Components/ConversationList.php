<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components;

use Closure;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\HasName;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
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
            $this->getCreateConversationAction()
        ], static::HEADER_ACTIONS_KEY);

        $this->childComponents(fn () => [
            $this->getCreateDirectConversationAction(),
            $this->getCreateGroupConversationAction(),
        ], static::CREATE_CONVERSATION_NESTED_ACTIONS_KEY);;

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

    public function conversationListOverflow(bool|Closure $condition = true): static
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
        // TODO: Add iconAlias namespace
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
        $action = Action::make('createDirectConversation')
            ->label(__('filament-converse::conversation-list.actions.create-direct.label'))
            ->modalHeading(__('filament-converse::conversation-list.actions.create-direct.modal-heading'))
            ->icon(Heroicon::OutlinedUser)
            ->modalWidth(Width::Large)
            ->model(Conversation::class)
            ->schema([
                Select::make('participations')
                    ->label(__('filament-converse::conversation-list.actions.create-direct.schema.participant'))
                    ->placeholder(__('filament-converse::conversation-list.actions.create-direct.schema.placeholder'))
                    ->required()
                    ->searchable()
                    ->allowHtml()
                    ->options(function () {
                        $user = auth()->user();

                        $options = $user::query()
                            ->whereKeyNot($user->getKey())
                            ->pluck($user::getFilamentNameAttribute(), $user->getKeyName())
                            ->map(function ($name) use ($user) {
                                $escapedName = e($name);

                                /* @var Model $user */

                                $avatarUrl = filament()->getUserAvatarUrl((new $user)->setAttribute($user::getFilamentNameAttribute(), $name));

                                return "
                                    <div style='display: flex; align-items: center; gap: 0.5rem;'>
                                        <img
                                            class='fi-avatar fi-circular sm'
                                            src='{$avatarUrl}'
                                            alt='{$escapedName}'
                                            style='padding: 2px'
                                        >
                                        <span>{$escapedName}</span>
                                    </div>
                                ";
                            });

                        if ($user instanceof HasAvatar || $user->hasAttribute('avatar_url')) {
                            // TODO: Do
                        }

                        return $options;
                    }),
                TextInput::make('name')
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(255),
            ])
            ->action(function (array $data) {

            });

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
        $action = Action::make('createGroupConversation')
            ->label(__('filament-converse::conversation-list.actions.create-group.label'))
            ->modalHeading(__('filament-converse::conversation-list.actions.create-group.modal-heading'))
            ->icon(Heroicon::OutlinedUserGroup)
            ->modalWidth(Width::Large)
            ->schema([

            ]);

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
