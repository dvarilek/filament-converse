<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Foundation\Auth\User as DefaultUser;

it('can create a conversation with a single participant', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser,
        [
            'name' => 'Test',
            'description' => 'Test description',
            'color' => 'primary',
        ]
    );

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->name->toBe('Test')
        ->description->toBe('Test description')
        ->and($conversation->participations)->toHaveCount(2)
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($otherUser->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($conversation->owner->participant->getKey())->toBe($owner->getKey());
});

it('can create a conversation with multiple participants', function () {
    $owner = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstUser, $secondUser]),
        [
            'name' => 'Test',
            'description' => 'Test description',
        ]
    );

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->name->toBe('Test')
        ->description->toBe('Test description')
        ->and($conversation->participations)->toHaveCount(3)
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($firstUser->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($secondUser->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($conversation->owner->participant->getKey())->toBe($owner->getKey());
});

it('throws an exception when the owner user is not a model that uses the Conversable trait', function () {
    $owner = DefaultUser::unguarded(function () {
        return DefaultUser::query()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => bcrypt('password'),
        ]);
    });
    $otherUser = User::factory()->create();

    expect(fn () => app(CreateConversation::class)->handle(
        $owner,
        $otherUser
    ))->toThrow(Exception::class);
});

it('throws an exception when a participating user is not a model that uses the Conversable trait', function () {
    $owner = User::factory()->create();
    $otherUser = DefaultUser::unguarded(function () {
        return DefaultUser::query()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    expect(fn () => app(CreateConversation::class)->handle(
        $owner,
        $otherUser
    ))->toThrow(Exception::class);
});

it('cannot create a conversation without participants', function () {
    $owner = User::factory()->create();

    expect(fn () => app(CreateConversation::class)->handle(
        $owner,
        collect()
    ))->toThrow(Exception::class);
});

it('cannot create a conversation with owner as one of its participants', function () {
    $owner = User::factory()->create();

    expect(fn () => app(CreateConversation::class)->handle(
        $owner,
        collect([$owner])
    ))->toThrow(Exception::class);
});

