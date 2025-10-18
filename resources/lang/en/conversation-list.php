<?php

declare(strict_types=1);

return [

    'heading' => 'Conversations',

    'search' => [
        
        'label' => 'Search',
        
        'placeholder' => 'Search',
        
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
            
            'schema' => [
                
                'participant' => 'Participant', 
                
                'placeholder' => 'Select a participant'
                
            ]
            
        ],
        
        'create-group' => [
            
            'label' => 'Group',
            
            'modal-heading' => 'Create a new group conversation',
            
        ]
        
    ],
    
    'empty-state' => [

        'heading' => 'No conversations found',

        'description' => 'You are not participating in any conversations yet.'

    ]
];
