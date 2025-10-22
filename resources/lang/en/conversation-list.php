<?php

declare(strict_types=1);

return [

    'heading' => 'Conversations',

    'search' => [

        'label' => 'Search',

        'placeholder' => 'Search',

    ],

    'last-message' => [

        'current-user' => 'You',

        'empty-state' => 'No messages yet',

    ],

    'actions' => [

        'create' => [

            'label' => 'Create',

            'modal-heading' => 'Create a new conversation',

            'modal-description' => 'Which conversation type would you like to create?',

        ],

        'create-direct' => [

            'label' => 'Direct',

            'modal-heading' => 'Create a new direct conversation',

            'modal-submit-action-label' => 'Create',

            'schema' => [

                'participant' => [

                    'label' => 'Participant',

                    'placeholder' => 'Select a participant',

                ],

            ],

        ],

        'create-group' => [

            'label' => 'Group',

            'modal-heading' => 'Create a new group conversation',

            'modal-submit-action-label' => 'Create',

            'schema' => [

                'participant' => [

                    'label' => 'Participants',

                    'placeholder' => 'Select a participants',

                ],

                'name' => [

                    'label' => 'Name',

                ],

                'description' => [

                    'label' => 'Description',

                ],

                'image' => [

                    'label' => 'Image',

                ],

            ],

        ],

        'notifications' => [

            'conversation-created-title' => 'Conversation created',

        ],

    ],

    'empty-state' => [

        'heading' => 'No conversations found',

        'description' => 'You are not participating in any conversations yet.',

    ],
];
