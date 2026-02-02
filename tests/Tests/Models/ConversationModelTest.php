<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;

it('can construct group conversation name from less than four participants', function () {
    $creator = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $this->actingAs($creator);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        collect([$firstUser, $secondUser])
    );

    expect($conversation->getName())
        ->toBe($firstUser->name . ' & ' . $secondUser->name);
});

it('can construct group conversation from more than four participants', function () {
    $creator = User::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $thirdUser = User::factory()->create();

    $this->actingAs($creator);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        collect([$firstUser, $secondUser, $thirdUser])
    );

    expect($conversation->getName())
        ->toBe($firstUser->name . ', ' . $secondUser->name . ' & ' . $thirdUser->name);
});

it('can construct direct conversation name from its participants', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($creator);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser
    );

    expect($conversation->getName())
        ->toBe($otherUser->name);
});

it('can retrieve other participations', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($creator);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $creator,
        $otherUser
    );

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->otherParticipations->toHaveCount(1)
        ->otherParticipations->value('participant_id')->toBe($otherUser->getKey());
});
