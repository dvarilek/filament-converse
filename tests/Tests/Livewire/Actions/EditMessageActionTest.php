<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\EditMessageAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

it('is only visible to message author', function () {
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
        ->assertActionVisible(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $ownerMessage->getKey(),
                ]),
        )
        ->assertActionHidden(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $participantMessage->getKey(),
                ]),
        );
});

it('is hidden when message content is empty', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);
    /* @var Message $message */
    $message = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => null,
    ]);

    livewire(ConversationManager::class)
        ->assertActionHidden(
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
        );
});

it('can update a message', function () {
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
            TestAction::make(EditMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
            [
                'messageContent' => 'updated message',
            ]
        )
        ->assertHasNoFormErrors();

    expect($message->fresh()->content)->toBe('updated message');
});
