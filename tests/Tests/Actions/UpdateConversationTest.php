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
        collect([$initialParticipant, $newParticipant]),
        [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]
    );

    expect($updatedConversation)
        ->toBeInstanceOf(Conversation::class)
        ->name->toBe('Updated Name')
        ->description->toBe('Updated Description')
        ->and($updatedConversation->participations()->whereNull('present_until')->count())->toBe(3)
        ->and($initialParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue()
        ->and($newParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue()
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue();
});

it('can update a conversation and remove participants by setting present_until', function () {
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

    expect($conversation->participations()->whereNull('present_until')->count())->toBe(3);

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        $firstParticipant,
        [
            'name' => 'Updated Test',
        ]
    );

    expect($updatedConversation->participations()->whereNull('present_until')->count())->toBe(2)
        ->and($updatedConversation->participations()->count())->toBe(3)
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue()
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeFalse()
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNotNull('present_until')->exists())->toBeTrue()
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue();
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
        $participant,
        [
            'name' => 'New Name',
            'description' => 'New Description',
        ]
    );

    expect($updatedConversation)
        ->name->toBe('New Name')
        ->description->toBe('New Description')
        ->and($updatedConversation->participations()->whereNull('present_until')->count())->toBe(2);
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
        $secondParticipant
    );

    expect($updatedConversation->owner->participant->getKey())->toBe($owner->getKey())
        ->and($updatedConversation->participations()->whereNull('present_until')->count())->toBe(2)
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue()
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeFalse()
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNotNull('present_until')->exists())->toBeTrue()
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue();
});

it('does not duplicate participants when updating with existing participants', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    expect($conversation->participations()->whereNull('present_until')->count())->toBe(2);

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        $participant
    );

    expect($updatedConversation->participations()->whereNull('present_until')->count())->toBe(2)
        ->and($updatedConversation->participations()->count())->toBe(2);
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
        collect([$validParticipant, $invalidParticipant])
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
        collect([$newParticipant1, $newParticipant2])
    );

    expect($updatedConversation->participations()->whereNull('present_until')->count())->toBe(3)
        ->and($updatedConversation->participations()->count())->toBe(5)
        ->and($oldParticipant1->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeFalse()
        ->and($oldParticipant1->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNotNull('present_until')->exists())->toBeTrue()
        ->and($oldParticipant2->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeFalse()
        ->and($oldParticipant2->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNotNull('present_until')->exists())->toBeTrue()
        ->and($newParticipant1->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue()
        ->and($newParticipant2->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue()
        ->and($owner->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue();
});

it('can re-add a previously removed participant without creating duplicate participation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $participationId = $participant
        ->conversationParticipations()
        ->where('conversation_id', $conversation->getKey())
        ->value('id');

    expect($conversation->participations()->count())->toBe(2);

    $updatedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([])
    );

    expect($updatedConversation->participations()->whereNull('present_until')->count())->toBe(1)
        ->and($participant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNotNull('present_until')->exists())->toBeTrue();

    $reAddedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        $participant
    );

    $reAddedParticipationId = $participant
        ->conversationParticipations()
        ->where('conversation_id', $conversation->getKey())
        ->whereNull('present_until')
        ->value('id');

    expect($reAddedConversation->participations()->count())->toBe(2)
        ->and($reAddedConversation->participations()->whereNull('present_until')->count())->toBe(2)
        ->and($participationId)->toBe($reAddedParticipationId)
        ->and($participant->conversationParticipations()->where('conversation_id', $conversation->getKey())->whereNull('present_until')->exists())->toBeTrue();
});

it('can re-add multiple previously removed participants without creating duplicates', function () {
    $owner = User::factory()->create();
    $firstParticipant = User::factory()->create();
    $secondParticipant = User::factory()->create();
    $thirdParticipant = User::factory()->create();

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        collect([$firstParticipant, $secondParticipant, $thirdParticipant])
    );

    expect($conversation->participations()->count())->toBe(4);

    app(UpdateConversation::class)->handle(
        $conversation,
        $thirdParticipant
    );

    expect($conversation->fresh()->participations()->whereNull('present_until')->count())->toBe(2)
        ->and($conversation->fresh()->participations()->count())->toBe(4);

    $reAddedConversation = app(UpdateConversation::class)->handle(
        $conversation,
        collect([$firstParticipant, $secondParticipant, $thirdParticipant])
    );

    expect($reAddedConversation->participations()->count())->toBe(4)
    ->and($reAddedConversation->participations()->whereNull('present_until')->count())->toBe(4)
        ->and($firstParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->count())->toBe(1)
        ->and($secondParticipant->conversationParticipations()->where('conversation_id', $conversation->getKey())->count())->toBe(1);
});

it('updates joined_at when re-adding a removed participant', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    Carbon::setTestNow(now());

    /* @var Conversation $conversation */
    $conversation = app(CreateConversation::class)->handle(
        $owner,
        $participant
    );

    $originalJoinedAt = $participant
        ->conversationParticipations()
        ->where('conversation_id', $conversation->getKey())
        ->value('joined_at');

    app(UpdateConversation::class)->handle(
        $conversation,
        collect([])
    );

    Carbon::setTestNow(now()->addMinute());

    app(UpdateConversation::class)->handle(
        $conversation,
        $participant
    );

    $newJoinedAt = $participant
        ->conversationParticipations()
        ->where('conversation_id', $conversation->getKey())
        ->whereNull('present_until')
        ->value('joined_at');

    expect($newJoinedAt)->not->toBe($originalJoinedAt)
        ->and($newJoinedAt)->toBeGreaterThan($originalJoinedAt);
});
