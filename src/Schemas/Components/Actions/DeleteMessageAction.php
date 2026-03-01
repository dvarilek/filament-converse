<?php

declare(strict_types=1);

namespace Dvarilek\FilamentConverse\Schemas\Components\Actions;

use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Icons\Heroicon;

class DeleteMessageAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'deleteMessage';
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->label(__('filament-converse::conversation-thread.message-actions.edit-message.label'));

        $this->color('danger');

        $this->requiresConfirmation();

        $this->icon(Heroicon::OutlinedTrash);

        $this->action(function () {
            $result = $this->process(static function (ConversationManager $livewire, array $arguments) {
                if (! ($recordKey = $arguments['record'] ?? null)) {
                    return false;
                }

                /* @var Message|null $message */
                $message = $livewire->getActiveConversationAuthenticatedUserParticipation()
                    ->messages()
                    ->firstWhere((new ConversationParticipation)->getKeyName(), $recordKey);

                if (! $message) {
                    return false;
                }

                return $message->delete();
            });

            if (! $result) {
                $this->failure();

                return;
            }

            $this->success();
        });

    }
}
