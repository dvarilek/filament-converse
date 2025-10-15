<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Tests\Models\User;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;
use Dvarilek\FilamentConverse\Models\Conversation;

it('can construct group conversation name from its participants', function () {
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

    expect($conversation->getName())
        ->toBe($creator->name . ', ' . $firstUser->name . ' & ' . $secondUser->name);
});

it('can construct direct conversation name from its participants', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($creator);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser,
        [
            'type' => ConversationTypeEnum::DIRECT,
        ]
    );

    expect($conversation->getName())
        ->toBe($otherUser->name);
});