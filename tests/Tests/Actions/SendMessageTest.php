<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\SendMessage;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
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
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    $sender = $conversation->createdBy;

    $message = app(SendMessage::class)->handle(
        $sender,
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
        ->sender->getKey()->toBe($sender->getKey())
        ->conversation->getKey()->toBe($conversation->getKey());
});

it('can send a message through message model', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    $sender = $conversation->createdBy;

    $message = $conversation->sendMessage($sender, [
        'content' => 'Test message',
    ]);

    expect($message)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('Test message')
        ->attachments->toBe([])
        ->sender->getKey()->toBe($sender->getKey())
        ->conversation->getKey()->toBe($conversation->getKey());
});

it('can send a reply to a message', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    $sender = $conversation->createdBy;

    $message = $conversation->sendMessage($sender, [
        'content' => 'First text message',
    ]);

    $reply = $conversation->sendMessage($sender, [
        'content' => 'Second text message',
        'reply_to_message_id' => $message->getKey(),
    ]);

    expect($reply)
        ->toBeInstanceOf(Message::class)
        ->content->toBe('Second text message')
        ->attachments->toBe([])
        ->sender->getKey()->toBe($sender->getKey())
        ->conversation->getKey()->toBe($conversation->getKey())
        ->reply->getKey()->toBe($message->getKey())
        ->and($message->replies)->toHaveCount(1)
        ->and($message->replies->first()->getKey())->toBe($reply->getKey());
});
