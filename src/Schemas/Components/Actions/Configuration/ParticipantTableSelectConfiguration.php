<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\Configuration;

use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ParticipantTableSelectConfiguration
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(static fn (TableSelectLivewireComponent $livewire, Table $table) => FilamentConverseServiceProvider::getFilamentConverseUserModel()::query()
                ->whereKeyNot(auth()->id())
                ->when(
                    $livewire->isDisabled && ($conversationKey = ($table->getArguments()['conversationKey'] ?? null)),
                    static fn (Builder $query) => $query->whereHas('conversationParticipations', static fn (Builder $subQuery) => $subQuery
                        ->where('conversation_id', $conversationKey)
                    )
                )
            )
            ->searchable()
            ->paginationPageOptions([
                'all'
            ])
            ->columns([
                Split::make([
                    ImageColumn::make('avatar')
                        ->circular()
                        ->grow(false)
                        ->getStateUsing(static fn (Authenticatable & Model $record) => filament()->getUserAvatarUrl($record)),
                    TextColumn::make('name'),
                    TextColumn::make('participant_type')
                        ->badge()
                        ->getStateUsing(static function (Authenticatable & Model $record, Table $table): ?string {
                            /* @var ?Conversation $conversation */
                            $conversation = Conversation::query()->find($table->getArguments()['conversationKey'] ?? null);

                            if (! $conversation) {
                                return null;
                            }

                            /* @var ?ConversationParticipation $participation */
                            $participation = $conversation->participations()->firstWhere('participant_id', $record->getKey());

                            if (! $participation) {
                                return null;
                            }

                            return $participation->getKey() === $conversation->owner_id
                                ? __('filament-converse::actions.schema.participants.participation.owner')
                                : __('filament-converse::actions.schema.participants.participation.default');
                        }),
                ])
            ]);
    }
}
