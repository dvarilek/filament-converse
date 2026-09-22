<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Configurations;

use Dvarilek\FilamentConverse\Schemas\Components\Contracts\HasMessageInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Schema;

class MessageInputSchemaConfiguration
{
    public static function configure(Schema $schema, HasMessageInput $component): Schema
    {
        return $schema
            ->schema([
                FusedGroup::make([
                    $component->getAttachmentAreaComponent(),
                    $component->getTextareaComponent(),
                    Actions::make([
                        $component->getUploadAttachmentAction(),
                        $component->getSendMessageAction(),
                    ])
                        ->alignBetween(),
                ])
                    ->extraAttributes([
                        'class' => 'fi-converse-conversation-thread-message-input',
                    ]),
            ]);
    }
}
