<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\Collections\ConversationParticipationCollection;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;

it('can retrieve other participations', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    $this->actingAs($owner);

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant])
    );

    $otherParticipations = $conversation->participations->other();

    expect($conversation)
        ->toBeInstanceOf(Conversation::class)
        ->and($otherParticipations)
        ->toBeInstanceOf(ConversationParticipationCollection::class)
        ->toHaveCount(2)
        ->and($otherParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$firstParticipant->getKey(), $secondParticipant->getKey()])->sort()->values()->toArray())
        ->toBe($conversation->participations()->other()->pluck('participant_id')->sort()->values()->toArray());
});

it('can retrieve active participations', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant])
    );

    $secondParticipant->conversationParticipations()
        ->firstWhere('conversation_id', $conversation->getKey())
        ->deactivate();

    $activeParticipations = $conversation->participations->active();

    expect($conversation->participations)
        ->toHaveCount(3)
        ->and($activeParticipations)
        ->toBeInstanceOf(ConversationParticipationCollection::class)
        ->toHaveCount(2)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $firstParticipant->getKey()])->sort()->values()->toArray())
        ->toBe($conversation->participations()->active()->pluck('participant_id')->sort()->values()->toArray());
});

it('can retrieve inactive participations', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant])
    );

    $secondParticipant->conversationParticipations()
        ->firstWhere('conversation_id', $conversation->getKey())
        ->deactivate();

    $inactiveParticipations = $conversation->participations->inactive();

    expect($conversation->participations)
        ->toHaveCount(3)
        ->and($inactiveParticipations)
        ->toBeInstanceOf(ConversationParticipationCollection::class)
        ->toHaveCount(1)
        ->and($inactiveParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe([$secondParticipant->getKey()])
        ->toBe($conversation->participations()->inactive()->pluck('participant_id')->sort()->values()->toArray());
});


