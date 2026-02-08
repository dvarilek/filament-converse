<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\Concerns;

use Dvarilek\FilamentConverse\Models\Conversation;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait CanManageConversation
{
    protected ?Closure $modifyParticipantSelectComponentUsing = null;

    protected ?Closure $modifyConversationNameComponentUsing = null;

    protected ?Closure $modifyConversationDescriptionComponentUsing = null;

    protected ?Closure $modifyConversationImageComponentUsing = null;

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

    public function getParticipantSelectComponent(): Field
    {
        $component = Select::make('participants')
            ->label(__('filament-converse::conversation-schema.participant.label'))
            ->placeholder(__('filament-converse::conversation-schema.participant.placeholder'))
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
                        ->has('participations', 2)
                        ->exists();

                    if ($directConversationExists) {
                        $fail(__('filament-converse::conversation-schema.participant.validation.direct-conversation-exists'));
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

    public function getConversationNameComponent(): Field
    {
        $component = TextInput::make('name')
            ->label(__('filament-converse::conversation-schema.name.label'))
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

    public function getConversationDescriptionComponent(): Field
    {
        $component = Textarea::make('description')
            ->label(__('filament-converse::conversation-schema.description.label'))
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

    public function getConversationImageComponent(): Field
    {
        $component = FileUpload::make('image')
            ->label(__('filament-converse::conversation-schema.image.label'))
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
