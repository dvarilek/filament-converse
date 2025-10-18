<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Foundation\Auth\User as DefaultUser;

it('can create a conversation with a single participant', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
            'name' => 'Test',
            'description' => 'Test description',
            'color' => 'primary',
        ]
    );

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->type->toBe(ConversationTypeEnum::DIRECT)
        ->name->toBe('Test')
        ->description->toBe('Test description')
        ->and($conversation->participations)->toHaveCount(2)
        ->and($creator->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($otherUser->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($conversation->createdBy->participant->getKey())->toBe($creator->getKey());
});

it('can create a conversation with multiple participants', function () {
    $creator = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        collect([$firstUser, $secondUser]),
        [
            'type' => ConversationTypeEnum::GROUP,
            'name' => 'Test',
            'description' => 'Test description',
        ]
    );

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->type->toBe(ConversationTypeEnum::GROUP)
        ->name->toBe('Test')
        ->description->toBe('Test description')
        ->and($conversation->participations)->toHaveCount(3)
        ->and($creator->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($firstUser->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($secondUser->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($conversation->createdBy->participant->getKey())->toBe($creator->getKey());
});

it('throws an exception when the creator user is not a model that uses the Conversable trait', function () {
    $creator = DefaultUser::unguarded(function () {
        return DefaultUser::query()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => bcrypt('password'),
        ]);
    });
    $otherUser = User::factory()->create();

    expect(fn () => app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    ))->toThrow(Exception::class);
});

it('throws an exception when a participating user is not a model that uses the Conversable trait', function () {
    $creator = User::factory()->create();
    $otherUser = DefaultUser::unguarded(function () {
        return DefaultUser::query()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    expect(fn () => app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    ))->toThrow(Exception::class);
});

it('cannot create a conversation without participants', function () {
    $creator = User::factory()->create();

    expect(fn () => app(CreateConversation::class)->handle(
        $creator,
        collect(),
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    ))->toThrow(Exception::class);
});

it('cannot create a direct conversation with multiple participants', function () {
    $creator = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    expect(fn () => app(CreateConversation::class)->handle(
        $creator,
        collect([$firstUser, $secondUser]),
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    ))->toThrow(Exception::class);
});
