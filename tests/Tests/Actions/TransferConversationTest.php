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

    expect($conversation->owner->participant->getKey())->toBe($owner->getKey());

    app(TransferConversation::class)->handle($conversation, $participant);

    expect($conversation->fresh()->owner->participant->getKey())->toBe($participant->getKey())
        ->and($conversation->fresh()->participations)->toHaveCount(2)
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($participant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();
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

    expect($conversation->owner->participant->getKey())->toBe($owner->getKey());

    app(TransferConversation::class)->handle($conversation, $firstParticipant);

    expect($conversation->fresh()->owner->participant->getKey())->toBe($firstParticipant->getKey());

    app(TransferConversation::class)->handle($conversation->fresh(), $secondParticipant);

    expect($conversation->fresh()->owner->participant->getKey())->toBe($secondParticipant->getKey())
        ->and($conversation->fresh()->participations)->toHaveCount(3);
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

it('preserves all participations when transferring ownership', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant])
    );

    $participationCountBefore = $conversation->participations()->count();

    app(TransferConversation::class)->handle($conversation, $firstParticipant);

    expect($conversation->fresh()->participations()->count())->toBe($participationCountBefore)
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();
});
