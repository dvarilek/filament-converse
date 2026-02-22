<?php

declare(strict_types=1);

return [

    'schema' => [

        'participants' => [

            'label' => 'Other participants',

            'validation' => [

                'direct-conversation-exists' => 'You already have a conversation with this participant.'

            ],

            'participation' => [

                'owner' => 'Owner',

                'default' => 'Participant'

            ]

        ],

        'participations' => [

            'label' => 'Participants',

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

    'create' => [

        'label' => 'Create',

        'modal-heading' => 'Create a new conversation',

        'modal-submit-action-label' => 'Create',

        'success-notification-title' => 'Conversation created'

    ],

    'manage' => [

        'label' => 'Manage',

        'modal-heading-view' => 'View conversation',

        'modal-heading-edit' => 'Edit conversation',

        'modal-submit-action-label' => 'Save',

        'success-notification-title' => 'Conversation updated',

        'advanced-actions' => [

            'label' => 'Advanced actions',

            'transfer-conversation-text' => 'Transfer ownership of this conversation to another participant.',

            'leave-conversation-text' => 'Remove yourself from this conversation. You will no longer receive messages.',

            'delete-conversation-text' => 'Permanently delete this conversation for all participants. This action cannot be undone.',

        ],
    ],

    'transfer' => [

        'label' => 'Transfer',

        'modal-heading' => 'Transfer conversation ownership?',

        'modal-heading-single-participant' => 'Transfer ownership to :name?',

        'modal-submit-action-label' => 'Transfer',

        'success-notification-title' => 'Conversation ownership transferred',

    ],

    'leave' => [

        'label' => 'Leave',

        'modal-heading' => 'Leave conversation?',

        'modal-submit-action-label' => 'Leave',

        'success-notification-title' => 'Conversation left',

    ],

    'delete' => [

        'label' => 'Delete',

        'modal-heading' => 'Delete conversation?',

        'modal-submit-action-label' => 'Delete',

        'success-notification-title' => 'Conversation deleted',

    ],

];
