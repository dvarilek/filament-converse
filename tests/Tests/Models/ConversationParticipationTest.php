<?php

declare(strict_types=1);

use Dvarilek\FilamentConverse\Tests\Models\User;
use Dvarilek\FilamentConverse\Actions\CreateConversation;
use Dvarilek\FilamentConverse\Models\ConversationParticipation;
use Dvarilek\FilamentConverse\Enums\ConversationTypeEnum;

it('can create conversation participation with participant name', function () {
    $firstUser = User::factory()->state(['name' => 'user name'])->create();
    $secondUser = User::factory()->create();

    /* @var ConversationParticipation $firstUserParticipation */
    $firstUserParticipation = app(CreateConversation::class)->handle(
        $firstUser,
        $secondUser,
        [
            'type' => ConversationTypeEnum::GROUP,
        ]
    )
        ->createdBy;


    expect($firstUserParticipation)->toBeInstanceOf(ConversationParticipation::class)
        ->participant_name->toBe('user name');
});