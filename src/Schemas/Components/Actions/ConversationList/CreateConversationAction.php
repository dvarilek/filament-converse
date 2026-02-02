<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList;

use Closure;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CreateConversationAction extends Action
{
    protected ?Closure $modifyParticipantSelectComponentUsing = null;

    protected ?Closure $modifyConversationNameComponentUsing = null;

    protected ?Closure $modifyConversationDescriptionComponentUsing = null;

    protected ?Closure $modifyConversationImageComponentUsing = null;

    protected ?Closure $modifyConversationCreatedNotificationUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'createConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-list.actions.create-conversation.label'));

        $this->modalHeading(__('filament-converse::conversation-list.actions.create-conversation.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-list.actions.create-conversation.modal-submit-action-label'));

        $this->icon(Heroicon::Plus);

        $this->modalWidth(Width::Large);

        $this->cancelParentActions();

        $this->schema(static fn (CreateConversationAction $action) => [
            $action->getParticipantSelectComponent(),
            Group::make([
                $action->getConversationNameComponent(),
                $action->getConversationDescriptionComponent(),
                $action->getConversationImageComponent(),
            ])
                ->visibleJs(<<<'JS'
                    $get('participants').length > 1
                 JS)

        ]);

        $this->action(function (CreateConversationAction $action, HasConversationSchema $livewire, array $data) {
            $user = auth()->user();
            /* @var Collection<int, Model&Authenticatable>|(Model&Authenticatable) $participants */
            $participants = $user::query()->whereIn($user->getKeyName(), $data['participants'])->get();

            $conversation = app(CreateConversation::class)->handle(
                $user,
                $participants,
                [
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null,
                ]
            );

            $livewire->updateActiveConversation($conversation->getKey());
            unset($livewire->conversations);

            $action->getConversationCreatedNotification()?->send();
        });
    }

    public function participantSelectComponent(?Closure $callback): static
    {
        $this->modifyParticipantSelectComponentUsing = $callback;

        return $this;
    }

    public function conversationNameComponent(?Closure $callback): static
    {
        $this->modifyConversationNameComponentUsing = $callback;

        return $this;
    }

    public function conversationDescriptionComponent(?Closure $callback): static
    {
        $this->modifyConversationDescriptionComponentUsing = $callback;

        return $this;
    }

    public function conversationImageComponent(?Closure $callback): static
    {
        $this->modifyConversationImageComponentUsing = $callback;

        return $this;
    }

    public function conversationCreatedNotification(?Closure $callback): static
    {
        $this->modifyConversationCreatedNotificationUsing = $callback;

        return $this;
    }

    protected function getParticipantSelectComponent(): Select
    {
        $component = Select::make('participants')
            ->label(__('filament-converse::conversation-list.actions.create-conversation.schema.participant.label'))
            ->placeholder(__('filament-converse::conversation-list.actions.create-conversation.schema.participant.placeholder'))
            ->required()
            ->searchable()
            ->allowHtml()
            ->multiple()
            ->options(function () {
                /* @var Authenticatable & Model $user */
                $user = auth()->user();

                return $user::query()
                    ->whereKeyNot($user->getKey())
                    ->pluck($user::getFilamentNameAttribute(), $user->getKeyName())
                    ->map(static function (string $name) use ($user): string {
                        $avatarUrl = filament()->getUserAvatarUrl((new $user)->setAttribute($user::getFilamentNameAttribute(), $name));
                        $name = e($name);

                        return "
                            <div style='display:flex;align-items:center;gap:0.5rem'>
                                <img class='fi-avatar fi-circular sm' src='{$avatarUrl}' alt='{$name}' style='padding:2px'>
                                <span>{$name}</span>
                            </div>
                        ";
                    })
                    ->toArray();
            })
            ->rule(
                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                    if (count($value) !== 1) {
                        return;
                    }

                    /* @var Authenticatable & Model $user */
                    $user = auth()->user();

                    $directConversationExists = Conversation::query()
                        ->whereHas('participations', static fn (Builder $query) => $query
                            ->where('participant_id', $user->getKey())
                        )
                        ->whereHas('participations', static fn (Builder $query) => $query
                            ->where('participant_id', head($value))
                        )
                        ->exists();

                    if ($directConversationExists) {
                        $fail(__('filament-converse::conversation-list.actions.create-conversation.schema.participant.validation.direct-conversation-exists'));
                    }
                }
            );

        if ($this->modifyParticipantSelectComponentUsing) {
            $component = $this->evaluate($this->modifyParticipantSelectComponentUsing, [
                'component' => $component,
            ], [
                Select::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    protected function getConversationNameComponent(): Field
    {
        $component = TextInput::make('name')
            ->label(__('filament-converse::conversation-list.actions.create-conversation.schema.name.label'))
            ->maxLength(255);

        if ($this->modifyConversationNameComponentUsing) {
            $component = $this->evaluate($this->modifyConversationNameComponentUsing, [
                'component' => $component,
            ], [
                TextInput::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    protected function getConversationDescriptionComponent(): Field
    {
        $component = Textarea::make('description')
            ->label(__('filament-converse::conversation-list.actions.create-conversation.schema.description.label'))
            ->maxLength(255);

        if ($this->modifyConversationDescriptionComponentUsing) {
            $component = $this->evaluate($this->modifyConversationDescriptionComponentUsing, [
                'component' => $component,
            ], [
                Textarea::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    protected function getConversationImageComponent(): Field
    {
        $component = FileUpload::make('image')
            ->label(__('filament-converse::conversation-list.actions.create-conversation.schema.image.label'))
            ->acceptedFileTypes(['image/png', 'image/jpeg'])
            ->avatar();

        if ($this->modifyConversationImageComponentUsing) {
            $component = $this->evaluate($this->modifyConversationImageComponentUsing, [
                'component' => $component,
            ], [
                FileUpload::class => $component,
            ]) ?? $component;
        }

        return $component;
    }

    protected function getConversationCreatedNotification(): ?Notification
    {
        $notification = Notification::make('conversationCreated')
            ->success()
            ->title(__('filament-converse::conversation-list.actions.notifications.conversation-created-title'));

        if ($this->modifyConversationCreatedNotificationUsing) {
            $notification = $this->evaluate($this->modifyConversationCreatedNotificationUsing, [
                'notification' => $notification,
            ], [
                Notification::class => $notification,
            ]);
        }

        return $notification;
    }
}
