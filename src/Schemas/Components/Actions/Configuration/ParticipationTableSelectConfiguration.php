<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\Configuration;

use Dvarilek\FilamentConverse\FilamentConverseServiceProvider;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParticipationTableSelectConfiguration
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(static function (Table $table): mixed {
                $conversation = Conversation::query()
                    ->whereKey($table->getArguments()['conversationKey'] ?? null)
                    ->first();

                if (!$conversation) {
                    return null;
                }

                return $conversation->participations()->whereKeyNot($conversation->owner_id);
            })
            ->searchable()
            ->paginationPageOptions([
                'all'
            ])
            ->columns([
                Split::make([
                    ImageColumn::make('avatar')
                        ->circular()
                        ->grow(false)
                        ->getStateUsing(static fn (ConversationParticipation $record) => filament()->getUserAvatarUrl($record->participant)),
                    TextColumn::make('name')
                        ->getStateUsing(static function (ConversationParticipation $record) {
                            $participant = $record->participant;

                            return $participant->getAttribute($participant::getFilamentNameAttribute());
                        }),
                ])
            ]);
    }
}
