<?php

declare(strict_types=1);

return [

    'empty-state' => [

        'heading' => 'No messages yet',

    ],

    'placeholder' => 'Message...',

    'attachment-modal' => [

        'heading' => 'Add Attachments',

        'description' => 'Drop your attachments here to add them to your message.',

        'file-attachments-accepted-file-types-validation-message' => 'Uploaded files must be of type: :values.',

        'file-attachments-max-size-validation-message' => 'Uploaded files must not be greater than :max kilobytes.',

        'max-file-attachments-validation-message' => '{1} You can only upload a maximum of :count attachment.|[2,*] You can only upload a maximum of :count attachments.',

    ],

    'message-actions' => [

        'delete-label' => 'Delete',

        'edit-message' => [

            'label' => 'Edit',

        ],

    ],

    'new-messages-divider-content' => [

        'label' => '{1} :count new message|[2,*] :count new messages',

    ],

    'message-divider-content' => [

        'today' => 'Today',

        'yesterday' => 'Yesterday',

    ],

    'footer-actions' => [

        'upload-attachment-label' => 'Upload Attachments',

        'send-message-label' => 'Send Message',

    ],

    'read-receipt' => [

        'seen' => 'Seen',

        'seen-by-one' => 'Seen by :name',

        'seen-by-two' => 'Seen by :firstName and :secondName',

        'seen-by-three' => 'Seen by :firstName, :secondName and :thirdName',

        'seen-by-many-shortened' => 'Seen by :firstName, :secondName, :thirdName and :othersCount others',

        'seen-by-everyone' => 'Seen by everyone',

        'seen-by-many-full' => 'Seen by :names and :lastName',

    ],

    'typing-indicator' => [

        'single' => '{singleName} is typing...',

        'double' => '{firstName} and {secondName} are typing...',

        'multiple' => '{firstName}, {secondName}, and {count} {others} are typing...',

        'other' => 'other',

        'others' => 'others',

    ],

    'attachments' => [

        'remove-button-label' => 'Remove',

        'validation-message-close-button-label' => 'Close',

        'mime-type' => [

            'image' => 'Image',

            'audio' => 'Audio',

            'video' => 'Video',

            'pdf' => 'PDF',

            'document' => 'Document',

            'spreadsheet' => 'Spreadsheet',

        ],

    ],
];
