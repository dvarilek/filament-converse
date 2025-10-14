<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Support\Collection;

it('can retrieve conversations for a specific user', function () {
    $creator = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    /* @var Conversation $firstConversation */
    $firstConversation = app(CreateConversation::class)->handle(
        $creator,
        collect([$firstUser, $secondUser]),
        [
            'type' => ConversationTypeEnum::GROUP,
        ]
    );

    /* @var Conversation $secondConversation */
    $secondConversation = app(CreateConversation::class)->handle(
        $creator,
        $firstUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    /* @var Conversation $thirdConversation */
    $thirdConversation = app(CreateConversation::class)->handle(
        $firstUser,
        $creator,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    /* @var Conversation $fourthConversation */
    $fourthConversation = app(CreateConversation::class)->handle(
        $secondUser,
        $firstUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    $conversationPrimaryKeyName = (new Conversation)->getKeyName();

    /* @var Collection<int, Conversation> $creatorConversations */
    $creatorConversations = $creator->conversations()->get();
    /* @var list<string> $creatorConversationPrimaryKeys */
    $creatorConversationPrimaryKeys = $creatorConversations->pluck($conversationPrimaryKeyName);

    expect($creatorConversations->count())->toBe(3)
        ->and($creatorConversationPrimaryKeys)->toContain(
            $firstConversation->getKey(),
            $secondConversation->getKey(),
            $thirdConversation->getKey(),
        )
        ->and($creatorConversationPrimaryKeys)->not->toContain(
            $fourthConversation->getKey()
        );

    /* @var Collection<int, Conversation> $firstUserConversations */
    $firstUserConversations = $firstUser->conversations()->get();
    /* @var list<string> $firstUserConversationPrimaryKeys */
    $firstUserConversationPrimaryKeys = $firstUserConversations->pluck($conversationPrimaryKeyName);

    expect($firstUserConversations->count())->toBe(4)
        ->and($firstUserConversationPrimaryKeys)->toContain(
            $firstConversation->getKey(),
            $secondConversation->getKey(),
            $thirdConversation->getKey(),
            $fourthConversation->getKey()
        );

    /* @var Collection<int, Conversation> $secondUserConversations */
    $secondUserConversations = $secondUser->conversations()->get();
    /* @var list<string> $secondUserConversationPrimaryKeys */
    $secondUserConversationPrimaryKeys = $secondUserConversations->pluck($conversationPrimaryKeyName);

    expect($secondUserConversations->count())->toBe(2)
        ->and($secondUserConversationPrimaryKeys)->toContain(
            $firstConversation->getKey(),
            $fourthConversation->getKey(),
        )
        ->and($secondUserConversationPrimaryKeys)->not->toContain(
            $secondConversation->getKey(),
            $thirdConversation->getKey(),
        );
});

it('conversation retrieval preloads relationships', function () {
    $creator = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        collect([$firstUser, $secondUser]),
        [
            'type' => ConversationTypeEnum::GROUP,
        ]
    );

    /* @var Conversation $retrievedConversation */
    $retrievedConversation = $creator->conversations->first();

    expect($conversation->getKey())
        ->toBe($retrievedConversation->getKey())
        ->and($retrievedConversation->relationLoaded('createdBy'))
        ->toBeTrue()
        ->and($retrievedConversation->relationLoaded('participations'))
        ->toBeTrue()
        ->and($retrievedConversation->createdBy->relationLoaded('participant'))
        ->toBeTrue()
        ->and($retrievedConversation->participations->first()->relationLoaded('participant'))
        ->toBeTrue();
});
