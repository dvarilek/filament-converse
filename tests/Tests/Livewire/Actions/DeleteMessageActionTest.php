<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\DeleteMessageAction;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ReplyToMessageAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

test('is only visible to message author ', function () {
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
            TestAction::make(DeleteMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $ownerMessage->getKey(),
                ]),
        )
        ->assertActionHidden(
            TestAction::make(DeleteMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $participantMessage->getKey(),
                ]),
        );
});

test('can delete a message', function () {
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
});

test('deleting message being replied to cancels reply', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle($owner, $participant);
    $messageKey = $conversation->participations()->first()->sendMessage($conversation, [
        'content' => 'message',
    ])->getKey();

    $livewire = livewire(ConversationManager::class)
        ->callAction(
            TestAction::make(ReplyToMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $messageKey,
                ]),
        )
        ->callAction(
            TestAction::make(DeleteMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $messageKey,
                ]),
        );

    expect($conversation->fresh()->messages()->count())
        ->toBe(0)
        ->and($livewire->instance()->isReplyingToMessage($messageKey))->toBeFalse();
});
