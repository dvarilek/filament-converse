<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;

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
