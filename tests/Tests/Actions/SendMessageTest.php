<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Models\Message;
use Dvarilek\FilamentConverse\Tests\Models\User;

it('can send a message', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
    );

    $author = $conversation->creator;

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
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
    );

    $author = $conversation->creator;

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
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser
    );

    $author = $conversation->creator;

    $message = $author->sendMessage($conversation, [
        'content' => 'First text message',
    ]);

    $reply = $conversation->otherParticipations->first()->sendMessage($conversation, [
        'content' => 'Second text message',
        'reply_to_message_id' => $message->getKey(),
    ]);

    expect($reply)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('Second text message')
        ->attachments->toBe([])
        ->author->getKey()->toBe($author->getKey())
        ->reply->getKey()->toBe($message->getKey())
        ->and($message->replies)->toHaveCount(1)
        ->and($message->replies->first()->getKey())->toBe($reply->getKey());
});
