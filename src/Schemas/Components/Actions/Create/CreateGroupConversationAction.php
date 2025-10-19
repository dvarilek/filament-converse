<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\Create;

use Closure;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationList;
use Dvarilek\FilamentConverse\View\FilamentConverseIconAlias;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;

class CreateGroupConversationAction extends CreateConversationAction
{
    protected ?Closure $modifyConversationNameComponentUsing = null;

    protected ?Closure $modifyConversationDescriptionComponentUsing = null;

    protected ?Closure $modifyConversationImageComponentUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'createGroupConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-list.actions.create-group.label'));

        $this->modalHeading(__('filament-converse::conversation-list.actions.create-group.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-list.actions.create-group.modal-submit-action-label'));

        $this->icon(FilamentIcon::resolve(FilamentConverseIconAlias::DIRECT_CONVERSATION) ?? Heroicon::OutlinedUserGroup);

        $this->modalWidth(Width::Large);

        $this->cancelParentActions();

        $this->schema([
            $this->getParticipantSelectComponent(),
            $this->getConversationNameComponent(),
            $this->getConversationDescriptionComponent(),
            $this->getConversationImageComponent(),
        ]);

        $this->action(function (CreateGroupConversationAction $action, HasConversationList $livewire, array $data) {
            $user = auth()->user();

            $conversation = app(CreateConversation::class)->handle(
                $user,
                $user::query()->whereIn($user->getKeyName(), $data['participants'])->get(),
                [
                    'type' => ConversationTypeEnum::GROUP,
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

    protected function getParticipantSelectComponent(): Field
    {
        $component = Select::make('participants')
            ->label(__('filament-converse::conversation-list.actions.create-group.schema.participant.label'))
            ->placeholder(__('filament-converse::conversation-list.actions.create-group.schema.participant.placeholder'))
            ->required()
            ->searchable()
            ->allowHtml()
            ->multiple()
            ->options(function () {
                $user = auth()->user();

                return $user::whereKeyNot($user->getKey())
                    ->pluck($user::getFilamentNameAttribute(), $user->getKeyName())
                    ->map(fn (string $name) => $this->generateConversationActionParticipantOption($name, $user))
                    ->toArray();
            });

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
            ->label(__('filament-converse::conversation-list.actions.create-group.schema.name.label'))
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
            ->label(__('filament-converse::conversation-list.actions.create-group.schema.description.label'))
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
            ->label(__('filament-converse::conversation-list.actions.create-group.schema.image.label'))
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
}
