<?php

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Livewire\ConversationManager;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Schemas\Components\Actions\ReplyToMessageAction;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Collection;

use function Pest\Livewire\livewire;

it('can reply to a message', function () {
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
            TestAction::make(ReplyToMessageAction::getDefaultName())
                ->schemaComponent('conversation_schema.conversation_thread')
                ->arguments([
                    'recordKey' => $message->getKey(),
                ]),
            [
                'messageContent' => 'reply message',
            ]
        )
        ->assertHasNoFormErrors();

    /* @var Collection<Message> $messages */
    $messages = $conversation->messages;
    $replyMessage = $messages->firstWhere('reply_to_message_id', $message->getKey());

    expect($messages)
        ->toHaveCount(2)
        ->and($replyMessage)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('reply message');
});

it('can reply with attachments to a message', function () {

})->skip();

