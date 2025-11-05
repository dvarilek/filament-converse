<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions\ConversationThread;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class EditMessageAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'editMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->iconButton();

        $this->color('primary');

        $this->icon(Heroicon::OutlinedPencil);

        $this->action(function (ConversationManager $livewire, Action $action, array $arguments) {
            if (! ($recordKey = $arguments['record'] ?? null)) {
                return;
            }

            /* @var Message|null $message */
            $message = $livewire->getActiveConversationAuthenticatedUserParticipation()
                ->messages()
                ->firstWhere((new ConversationParticipation)->getKeyName(), $recordKey);

            if (! $message) {
                return;
            }

            // TODO:
        });
    }
}
