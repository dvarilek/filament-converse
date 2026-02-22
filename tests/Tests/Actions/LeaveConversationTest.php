<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\LeaveConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Foundation\Auth\User as DefaultUser;

it('can leave a conversation as a participant', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $result = app(LeaveConversation::class)->handle($conversation, $participant);

    $conversation = $conversation->fresh();

    $activeParticipations = $conversation->participations->active();
    $inactiveParticipations = $conversation->participations->inactive();

    expect($result)
        ->toBeTrue()
        ->and($activeParticipations)
        ->toHaveCount(1)
        ->and($activeParticipations->value('participant_id'))
        ->toBe($owner->getKey())
        ->and($inactiveParticipations)
        ->toHaveCount(1)
        ->and($inactiveParticipations->value('participant_id'))
        ->toBe($participant->getKey());
});

it('can leave a conversation with multiple participants', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant])
    );

    $result = app(LeaveConversation::class)->handle($conversation, $firstParticipant);

    $conversation = $conversation->fresh();

    $activeParticipations = $conversation->participations->active();
    $inactiveParticipations = $conversation->participations->inactive();

    expect($result)
        ->toBeTrue()
        ->and($activeParticipations)
        ->toHaveCount(2)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $secondParticipant->getKey()])->sort()->values()->toArray())
        ->and($inactiveParticipations)
        ->toHaveCount(1)
        ->and($inactiveParticipations->value('participant_id'))
        ->toBe($firstParticipant->getKey());
});

it('throws an exception when the owner tries to leave the conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    expect(fn () => app(LeaveConversation::class)->handle($conversation, $owner))
        ->toThrow(Exception::class);
});

it('throws an exception when inactive participant tries to leave the conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $conversation->participations()
        ->firstWhere('participant_id', $participant->getKey())
        ->deactivate();

    expect(fn () => app(LeaveConversation::class)->handle($conversation, $participant))
        ->toThrow(Exception::class);
});

it('throws an exception when a non-participant tries to leave the conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $nonParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    expect(fn () => app(LeaveConversation::class)->handle($conversation, $nonParticipant))
        ->toThrow(Exception::class);
});

it('throws an exception when a user not using the Conversable trait tries to leave', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $invalidUser = DefaultUser::unguarded(function () {
        return DefaultUser::query()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    expect(fn () => app(LeaveConversation::class)->handle($conversation, $invalidUser))
        ->toThrow(Exception::class);
});
