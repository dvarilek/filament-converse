<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\TransferConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Foundation\Auth\User as DefaultUser;

it('can transfer conversation ownership to another participant', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $result = app(TransferConversation::class)->handle($conversation, $participant);

    $activeParticipations = $conversation->fresh()->participations->active();

    expect($result)
        ->toBeTrue()
        ->and($activeParticipations)
        ->toHaveCount(2)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $participant->getKey()])->sort()->values()->toArray())
        ->and($conversation->owner->participant->getKey())
        ->toBe($participant->getKey());
});

it('can transfer conversation ownership between multiple participants', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant])
    );

    $result = app(TransferConversation::class)->handle($conversation, $firstParticipant);

    expect($result)
        ->toBeTrue()
        ->and($conversation->fresh()->owner->participant->getKey())
        ->toBe($firstParticipant->getKey());

    $result = app(TransferConversation::class)->handle($conversation->fresh(), $secondParticipant);

    expect($result)
        ->toBeTrue()
        ->and($conversation->fresh()->owner->participant->getKey())
        ->toBe($secondParticipant->getKey());
});

it('throws an exception when transferring to a non-participant', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $nonParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    expect(fn () => app(TransferConversation::class)->handle($conversation, $nonParticipant))
        ->toThrow(Exception::class);
});

it('throws an exception when transferring to inactive participant', function () {
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

    expect(fn () => app(TransferConversation::class)->handle($conversation, $participant))
        ->toThrow(Exception::class);
});

it('throws an exception when transferring to a user not using the Conversable trait', function () {
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

    expect(fn () => app(TransferConversation::class)->handle($conversation, $invalidUser))
        ->toThrow(Exception::class);
});
