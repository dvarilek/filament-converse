<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\UpdateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Foundation\Auth\User as DefaultUser;

it('can update a conversation with new participants', function () {
    $owner = User::factory()->create();
    $initialParticipant = User::factory()->create();
    $newParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $initialParticipant,
        [
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]
    );

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $initialParticipant, $newParticipant]),
        [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]
    );

    expect($updatedConversation)
        ->toBeInstanceOf(Conversation::class)
        ->name->toBe('Updated Name')
        ->description->toBe('Updated Description')
        ->and($updatedConversation->participations)->toHaveCount(3)
        ->and($initialParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($newParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();
});

it('can update a conversation and remove participants', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant]),
        [
            'name' => 'Test',
        ]
    );

    expect($conversation->participations)->toHaveCount(3);

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $firstParticipant]),
        [
            'name' => 'Updated Test',
        ]
    );

    expect($updatedConversation->participations)->toHaveCount(2)
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeFalse()
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();
});

it('can update a conversation attributes without changing participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant,
        [
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]
    );

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $participant]),
        [
            'name' => 'New Name',
            'description' => 'New Description',
        ]
    );

    expect($updatedConversation)
        ->name->toBe('New Name')
        ->description->toBe('New Description')
        ->and($updatedConversation->participations)->toHaveCount(2);
});

it('preserves the owner when updating a conversation', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $firstParticipant
    );

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $secondParticipant])
    );

    expect($updatedConversation->owner->participant->getKey())->toBe($owner->getKey())
        ->and($updatedConversation->participations)->toHaveCount(2)
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeFalse()
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();
});

it('does not duplicate participants when updating with existing participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    expect($conversation->participations)->toHaveCount(2);

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $participant])
    );

    expect($updatedConversation->participations)->toHaveCount(2);
});

it('throws an exception when updating with a participant that does not use the Conversable trait', function () {
    $owner = User::factory()->create();
    $validParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $validParticipant
    );

    $invalidParticipant = DefaultUser::unguarded(function () {
        return DefaultUser::query()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => bcrypt('password'),
        ]);
    });

    expect(fn () => app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $validParticipant, $invalidParticipant])
    ))->toThrow(Exception::class);
});

it('can update a conversation with multiple participants replacing all previous ones', function () {
    $owner = User::factory()->create();
    $oldParticipant1 = User::factory()->create();
    $oldParticipant2 = User::factory()->create();
    $newParticipant1 = User::factory()->create();
    $newParticipant2 = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$oldParticipant1, $oldParticipant2])
    );

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$owner, $newParticipant1, $newParticipant2])
    );

    expect($updatedConversation->participations)->toHaveCount(3)
        ->and($oldParticipant1->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeFalse()
        ->and($oldParticipant2->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeFalse()
        ->and($newParticipant1->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($newParticipant2->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue()
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->exists())->toBeTrue();
});
