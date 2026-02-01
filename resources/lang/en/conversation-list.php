<?php

declare(strict_types=1);

return [

    'heading' => 'Conversations',

    'list-close-button-label' => 'Close',

    'search' => [

        'label' => 'Search',

        'placeholder' => 'Search',

    ],

    'latest-message' => [

        'current-user' => 'You',

        'only-attachments' => '{1} Sent :count attachment|[2,*] Sent :count attachments',

        'empty-state' => 'No messages yet',

    ],

    'actions' => [

        'create-conversation' => [

            'label' => 'Create',

            'modal-heading' => 'Create a new conversation',

            'modal-submit-action-label' => 'Create',

            'schema' => [

                'participant' => [

                    'label' => 'Participants',

                    'placeholder' => 'Select a participants',

                    'validation' => [

                        'direct-conversation-exists' => 'You already have a conversation with this participant.'

                    ]

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
