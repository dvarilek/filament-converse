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

    expect($conversation->participations)->toHaveCount(2)
        ->and($participant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();

    app(LeaveConversation::class)->handle($conversation, $participant);

    expect($conversation->fresh()->participations)->toHaveCount(2)
        ->and($participant->conversationParticipations()->firstWhere('conversation_id', $conversation->getKey())->present_until)->not->toBeNull()
        ->and($owner->conversationParticipations()->firstWhere('conversation_id', $conversation->getKey())->present_until)->toBeNull();
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

    expect($conversation->participations)->toHaveCount(3);

    app(LeaveConversation::class)->handle($conversation, $firstParticipant);

    expect($conversation->fresh()->participations)->toHaveCount(3)
        ->and($firstParticipant->conversationParticipations()->firstWhere('conversation_id', $conversation->getKey())->present_until)->not->toBeNull()
        ->and($secondParticipant->conversationParticipations()->firstWhere('conversation_id', $conversation->getKey())->present_until)->toBeNull()
        ->and($owner->conversationParticipations()->firstWhere('conversation_id', $conversation->getKey())->present_until)->toBeNull();
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
