<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationList;

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Livewire\Contracts\HasConversationSchema;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class CreateDirectConversationAction extends CreateConversationAction
{
    public static function getDefaultName(): ?string
    {
        return 'createDirectConversation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-converse::conversation-list.actions.create-direct.label'));

        $this->modalHeading(__('filament-converse::conversation-list.actions.create-direct.modal-heading'));

        $this->modalSubmitActionLabel(__('filament-converse::conversation-list.actions.create-direct.modal-submit-action-label'));

        $this->icon(Heroicon::OutlinedUser);

        $this->modalWidth(Width::Large);

        $this->cancelParentActions();

        $this->schema([
            $this->getParticipantSelectComponent(),
        ]);

        $this->action(function (CreateDirectConversationAction $action, HasConversationSchema $livewire, array $data) {
            $user = auth()->user();

            $conversation = app(CreateConversation::class)->handle(
                $user,
                $user::query()->find($data['participant']),
                [
                    'type' => ConversationTypeEnum::DIRECT,
                ]
            );

            $livewire->updateActiveConversation($conversation->getKey());
            unset($livewire->conversations);

            $action->getConversationCreatedNotification()?->send();
        });
    }

    protected function getParticipantSelectComponent(): Field
    {
        $component = Select::make('participant')
            ->label(__('filament-converse::conversation-list.actions.create-direct.schema.participant.label'))
            ->placeholder(__('filament-converse::conversation-list.actions.create-direct.schema.participant.placeholder'))
            ->required()
            ->searchable()
            ->allowHtml()
            ->options(function () {
                $user = auth()->user();

                return $user::whereKeyNot($user->getKey())
                    ->excludeSharedDirectConversationsWith($user)
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
}
