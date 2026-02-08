<?php

declare(strict_types=1);

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
