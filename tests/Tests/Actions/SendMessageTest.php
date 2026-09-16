<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Exception;

it('can send a message', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser,
    );

    $author = $conversation->owner;

    $message = app(SendMessage::class)->handle(
        $author,
        $conversation,
        [
            'content' => 'Test message',
            'attachments' => [],
        ]
    );

    expect($message)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('Test message')
        ->attachments->toBe([])
        ->author->getKey()->toBe($author->getKey());
});

it('can send a message through message model', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser,
    );

    $author = $conversation->owner;

    $message = $author->sendMessage($conversation, [
        'content' => 'Test message',
    ]);

    expect($message)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('Test message')
        ->attachments->toBe([])
        ->author->getKey()->toBe($author->getKey());
});

it('can send a reply to a message', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser
    );

    $author = $conversation->owner;

    $message = $author->sendMessage($conversation, [
        'content' => 'First text message',
    ]);

    /* @var ConversationParticipation $otherUserParticipation */
    $otherUserParticipation = $conversation->participations()->firstWhere('participant_id', $otherUser->getKey());

    $reply = $otherUserParticipation->sendMessage($conversation, [
        'content' => 'Second text message',
        'reply_to_message_id' => $message->getKey(),
    ]);

    expect($reply)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('Second text message')
        ->attachments->toBe([])
        ->author->getKey()->toBe($otherUserParticipation->getKey())
        ->reply->getKey()->toBe($message->getKey())
        ->and($message->replies)->toHaveCount(1)
        ->and($message->replies->first()->getKey())->toBe($reply->getKey());
});

it('cannot reply to a message from different conversation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $firstConversation */
    $firstConversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser
    );
    /* @var Conversation $secondConversation */
    $secondConversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser
    );

    $secondConversationMessage = $secondConversation->owner->sendMessage($secondConversation, [
        'content' => 'Second text message',
    ]);

    expect(fn () => $firstConversation->owner->sendMessage($firstConversation, [
        'content' => 'Message',
        'reply_to_message_id' => $secondConversationMessage->getKey(),
    ]))
        ->toTHrow(Exception::class);
});
