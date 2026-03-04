<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\DeleteMessageAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

it('is only visible to message author ', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);

    /* @var Message $ownerMessage */
    $ownerMessage = $conversation
        ->participations()
        ->firstWhere('participant_id', $owner->getKey())
        ->sendMessage($conversation, [
            'content' => 'test',
        ]);

    /* @var Message $participantMessage */
    $participantMessage = $conversation
        ->participations()
        ->firstWhere('participant_id', $participant->getKey())
        ->sendMessage($conversation, [
            'content' => 'test',
        ]);

    livewire(ConversationManager::class)
        ->assertActionExists(
            TestAction::make(DeleteMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $ownerMessage->getKey(),
                ]),
            checkActionUsing: fn (DeleteMessageAction $action): bool => $action->record($ownerMessage)->isVisible(),
            generateMessageUsing: fn (string $prettyName, string $livewireClass): string => "Failed asserting that an action with name [{$prettyName}] is visible on the [{$livewireClass}] component.",
        )
        ->assertActionExists(
            TestAction::make(DeleteMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $participantMessage->getKey(),
                ]),
            checkActionUsing: fn (DeleteMessageAction $action): bool => $action->record($participantMessage)->isHidden(),
            generateMessageUsing: fn (string $prettyName, string $livewireClass): string => "Failed asserting that an action with name [{$prettyName}] is visible on the [{$livewireClass}] component.",
        );
});

it('can delete a message', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);
    /* @var Message $message */
    $message = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => 'message',
    ]);

    livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(DeleteMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
        );

    expect($conversation->fresh()->messages()->count())->toBe(0);
})->todo('Figure out how to make $message resolved');
