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
use Illuminate\Support\Arr;

class ParticipantTableSelectConfiguration
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery(...))
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
                    TextColumn::make('name')
                        ->weight('bold'),
                    TextColumn::make('ownership')
                        ->badge()
                        ->getStateUsing(static function (Authenticatable & Model $record, Table $table): ?string {
                            /* @var ?Conversation $conversation */
                            $conversation = Conversation::query()->find($table->getArguments()['conversationKey'] ?? null);

                            if (! $conversation) {
                                return null;
                            }

                            /* @var ?ConversationParticipation $participation */
                            $participation = $conversation
                                ->participations()
                                ->active()
                                ->firstWhere('participant_id', $record->getKey());

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

    public static function getTableQuery(TableSelectLivewireComponent $livewire, Table $table): Builder
    {
        $conversationKey = $table->getArguments()['conversationKey'] ?? null;
        $userModel = FilamentConverseServiceProvider::getFilamentConverseUserModel();

        $query = $userModel::query()->whereKeyNot(auth()->id());

        if (! $conversationKey) {
            return $query;
        }

        return $query;
        $activeParticipantsQuery = $query
            ->clone()
            ->whereHas('conversationParticipations', static fn (Builder $subQuery) => $subQuery
                ->where('conversation_id', $conversationKey)
                ->active()
            );

        $livewire->state = array_values(
            array_intersect($livewire->state, $activeParticipantsQuery->toBase()->pluck((new $userModel)->getKeyName())->toArray())
        );

        return $livewire->isDisabled ? $activeParticipantsQuery : $query;
    }
}
