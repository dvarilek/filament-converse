<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Support\Collection;

it('can retrieve conversations for a specific user', function () {
    $owner = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    /* @var Conversation $firstConversation */
    $firstConversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstUser, $secondUser])
    );

    /* @var Conversation $secondConversation */
    $secondConversation = app(CreateConversation::class)->handle(
        $owner,
        $firstUser
    );

    /* @var Conversation $thirdConversation */
    $thirdConversation = app(CreateConversation::class)->handle(
        $firstUser,
        $owner
    );

    /* @var Conversation $fourthConversation */
    $fourthConversation = app(CreateConversation::class)->handle(
        $secondUser,
        $firstUser
    );

    $conversationPrimaryKeyName = (new Conversation)->getKeyName();

    /* @var Collection<int, Conversation> $ownerConversations */
    $ownerConversations = $owner->conversations()->get();
    /* @var list<string> $ownerConversationKeys */
    $ownerConversationKeys = $ownerConversations->pluck($conversationPrimaryKeyName);

    expect($ownerConversations->count())->toBe(3)
        ->and($ownerConversationKeys)->toContain(
            $firstConversation->getKey(),
            $secondConversation->getKey(),
            $thirdConversation->getKey(),
        )
        ->and($ownerConversationKeys)->not->toContain(
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
