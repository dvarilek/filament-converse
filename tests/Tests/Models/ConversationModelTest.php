<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;

it('can retrieve other participations', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($owner);

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $otherUser
    );

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->otherParticipations->toHaveCount(1)
        ->otherParticipations->value('participant_id')->toBe($otherUser->getKey());
});
