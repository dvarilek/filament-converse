<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\Concerns;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\Configuration\ParticipantTableSelectConfiguration;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ManageConversationAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

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
        $component = TableSelect::make('participants')
            ->label(__('filament-converse::actions.schema.participants.label'))
            ->tableConfiguration(ParticipantTableSelectConfiguration::class)
            ->tableArguments(static fn (string $operation, ConversationManager $livewire) => [
                'conversationKey' =>  $operation === ManageConversationAction::getDefaultName() ? $livewire->getActiveConversation()->getKey() : null
            ])
            ->multiple()
            ->required()
            ->extraAttributes([
                'class' => 'fi-converse-table-select',
            ]);

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
            ->label(__('filament-converse::actions.schema.name.label'))
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
            ->label(__('filament-converse::actions.schema.description.label'))
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
            ->label(__('filament-converse::actions.schema.image.label'))
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
