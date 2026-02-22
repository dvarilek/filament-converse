<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Actions\UpdateConversation;
use Dvarilek\FilamentConverse\Models\Conversation;
use Dvarilek\FilamentConverse\Tests\Models\User;
use Illuminate\Foundation\Auth\User as DefaultUser;

it('can update a conversation with new participants', function () {
    $owner = User::factory()->create();
    $initialParticipant = User::factory()->create();
    $newParticipant = User::factory()->create();

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
        collect([$initialParticipant, $newParticipant]),
        [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]
    );

    $activeParticipations = $conversation->participations->active();

    expect($updatedConversation)
        ->toBeInstanceOf(Conversation::class)
        ->name->toBe('Updated Name')
        ->description->toBe('Updated Description')
        ->and($activeParticipations)->toHaveCount(3)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $initialParticipant->getKey(), $newParticipant->getKey()])->sort()->values()->toArray());
});

it('can update a conversation and remove participants by setting deactivated_at', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant]),
        [
            'name' => 'Test',
        ]
    );

    expect($conversation->participations->active())->toHaveCount(3);

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        $firstParticipant,
        [
            'name' => 'Updated Test',
        ]
    );

    $activeParticipations = $conversation->participations->active();
    $inactiveParticipations = $conversation->participations->inactive();

    expect($activeParticipations)->toHaveCount(2)
        ->and($inactiveParticipations)->toHaveCount(1)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $firstParticipant->getKey()])->sort()->values()->toArray())
        ->and($inactiveParticipations->value('participant_id'))
        ->toBe($secondParticipant->getKey());
});

it('can update a conversation attributes without changing participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant,
        [
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]
    );

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        $participant,
        [
            'name' => 'New Name',
            'description' => 'New Description',
        ]
    );

    expect($conversation)
        ->name->toBe('New Name')
        ->description->toBe('New Description')
        ->and($conversation->participations->active())->toHaveCount(2);
});

it('does not duplicate participants when updating with existing participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $originalActiveParticipations = $conversation->participations->active();

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        $participant
    );

    $activeParticipations = $conversation->participations->active();

    expect($originalActiveParticipations)
        ->toHaveCount(2)
        ->and($activeParticipations)
        ->toHaveCount(2)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe($originalActiveParticipations->pluck('participant_id')->sort()->values()->toArray());
});

it('throws an exception when updating with a participant that does not use the Conversable trait', function () {
    $owner = User::factory()->create();
    $validParticipant = User::factory()->create();

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
        collect([$validParticipant, $invalidParticipant])
    ))->toThrow(Exception::class);
});

it('can update a conversation with multiple participants replacing all previous ones', function () {
    $owner = User::factory()->create();
    $oldFirstParticipant = User::factory()->create();
    $oldSecondParticipant = User::factory()->create();
    $newFirstParticipant = User::factory()->create();
    $newSecondParticipant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$oldFirstParticipant, $oldSecondParticipant])
    );

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$newFirstParticipant, $newSecondParticipant])
    );

    $activeParticipations = $conversation->participations->active();

    expect($activeParticipations)
        ->toHaveCount(3)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $newFirstParticipant->getKey(), $newSecondParticipant->getKey()])->sort()->values()->toArray());
});

it('can re-add a previously removed participant without creating duplicate participation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    expect($conversation->participations->active())->toHaveCount(2);

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([])
    );

    expect($conversation->participations->active())
        ->toHaveCount(1)
        ->value('participant_id')
        ->toBe($owner->getKey())
        ->and($conversation->participations->inactive())
        ->toHaveCount(1)
        ->value('participant_id')
        ->toBe($participant->getKey());

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        $participant
    );

    $activeParticipations = $conversation->participations->active();

    expect($activeParticipations)
        ->toHaveCount(2)
        ->and($activeParticipations->pluck('participant_id')->sort()->values()->toArray())
        ->toBe(collect([$owner->getKey(), $participant->getKey()])->sort()->values()->toArray())
        ->and($conversation->participations->inactive())
        ->toHaveCount(0);
});

it('updates joined_at when re-adding a removed participant', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    Carbon::setTestNow(now());

    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $originalJoinedAt = $conversation
        ->participations
        ->active()
        ->firstWhere('participant_id', $participant->getKey())
        ->joined_at;

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([])
    );

    Carbon::setTestNow(now()->addMinute());

    $conversation = app(UpdateConversation::class)->handle(
        $conversation,
        $participant
    );

    $newJoinedAt = $conversation
        ->participations
        ->active()
        ->firstWhere('participant_id', $participant->getKey())
        ->joined_at;

    expect($newJoinedAt)
        ->not->toBe($originalJoinedAt)
        ->and($newJoinedAt)
        ->toBeGreaterThan($originalJoinedAt);
});
