<?php

declare(strict_types=1);

use Carbon\Carbon;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Tests\Models\User;

it('can create conversation participation with participant name', function () {
    $firstUser = User::factory()->state(['name' => 'user name'])->create();
    $secondUser = User::factory()->create();

    /* @var ConversationParticipation $firstUserParticipation */
    $ownerParticipation = app(CreateConversation::class)->handle(
        $firstUser,
        $secondUser
    )
        ->owner;

    expect($ownerParticipation)->toBeInstanceOf(ConversationParticipation::class)
        ->participant->name->toBe('user name');
});

it('can deactivate participation', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $firstUser,
        $secondUser
    );

    /* @var ConversationParticipation $secondParticipation */
    $secondParticipation = $conversation->participations()
        ->firstWhere('participant_id', $secondUser->getKey());

    $secondParticipation->deactivate();

    expect($secondParticipation->fresh()->deactivated_at)->not->toBeNull();
});

it('can deactivate many participations', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $thirdUser = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        $firstUser,
        collect([$secondUser, $thirdUser])
    );

    $conversation->participations()
        ->whereIn('participant_id', [$secondUser->getKey(), $thirdUser->getKey()])
        ->deactivateMany();

    $conversation = $conversation->fresh();

    expect($conversation->participations()->firstWhere('participant_id', $firstUser->getKey()))->deactivated_at->toBeNull()
        ->and($conversation->participations()->firstWhere('participant_id', $secondUser->getKey())->deactivated_at)->not->toBeNull()
        ->and($conversation->participations()->firstWhere('participant_id', $thirdUser->getKey()))->deactivated_at->not->toBeNull();
});

IT('can activate participation', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Carbon::setTestNow(now());

    $conversation = app(CreateConversation::class)->handle(
        $firstUser,
        $secondUser
    );

    /* @var ConversationParticipation $secondParticipation */
    $secondParticipation = $conversation->participations()
        ->firstWhere('participant_id', $secondUser->getKey());

    $secondParticipation->deactivate();
    $oldJoinedAt = $secondParticipation->joined_at;

    Carbon::setTestNow(now()->addMinute());

    $secondParticipation->activate();
    $joinedAtAfterActivate = $secondParticipation->fresh()->joined_at;

    expect($secondParticipation->fresh())
        ->deactivated_at->toBeNull()
        ->and($joinedAtAfterActivate)->toBeGreaterThan($oldJoinedAt);
});

it('can activate many participations', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $thirdUser = User::factory()->create();

    Carbon::setTestNow(now());

    $conversation = app(CreateConversation::class)->handle(
        $firstUser,
        collect([$secondUser, $thirdUser])
    );

    /* @var ConversationParticipation $firstUserParticipation */
    $firstUserParticipation = $conversation->participations()->firstWhere('participant_id', $firstUser->getKey());
    /* @var ConversationParticipation $secondUserParticipation */
    $secondUserParticipation = $conversation->participations()->firstWhere('participant_id', $secondUser->getKey());
    /* @var ConversationParticipation $thirdUserParticipation */
    $thirdUserParticipation = $conversation->participations()->firstWhere('participant_id', $thirdUser->getKey());

    $secondUserParticipation->deactivate();
    $thirdUserParticipation->deactivate();

    $oldJoinedAtSecond = $secondUserParticipation->joined_at;
    $oldJoinedAtThird = $thirdUserParticipation->joined_at;

    Carbon::setTestNow(now()->addMinute());

    $conversation->participations()
        ->whereIn('participant_id', [$secondUser->getKey(), $thirdUser->getKey()])
        ->activateMany();

    $conversation->fresh();
    $secondUserParticipation = $secondUserParticipation->fresh();
    $thirdUserParticipation = $thirdUserParticipation->fresh();

    $newJoinedAtSecond = $secondUserParticipation->joined_at;
    $newJoinedAtThird = $thirdUserParticipation->joined_at;

    expect($firstUserParticipation->fresh()->deactivated_at)->toBeNull()
        ->and($secondUserParticipation->deactivated_at)->toBeNull()
        ->and($thirdUserParticipation->deactivated_at)->toBeNull()
        ->and($newJoinedAtSecond)->toBeGreaterThan($oldJoinedAtSecond)
        ->and($newJoinedAtThird)->toBeGreaterThan($oldJoinedAtThird);
});
