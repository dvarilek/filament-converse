<?php

declare(strict_types=1);

return [

    'heading' => 'Konverzace',

    'list-close-button-label' => 'Zavřít',

    'search' => [

        'label' => 'Hledat',

        'placeholder' => 'Hledat',

    ],

    'latest-message' => [

        'current-user' => 'Vy',

        'only-attachments' => '{1} Odeslána :count příloha|[2,4] Odeslány :count přílohy|[5,*] Odesláno :count příloh',

        'empty-state' => 'Zatím žádné zprávy',

    ],

    'actions' => [

        'create-conversation' => [

            'label' => 'Vytvořit',

            'modal-heading' => 'Vytvořit novou konverzaci',

            'modal-submit-action-label' => 'Vytvořit',

            'schema' => [

                'participant' => [

                    'label' => 'Účastníci',

                    'placeholder' => 'Vyberte účastníky',

                    'validation' => [

                        'direct-conversation-exists' => 'S tímto uživatelem již máte konverzaci.'

                    ]

                ],

                'name' => [

                    'label' => 'Název',

                ],

                'description' => [

                    'label' => 'Popis',

                ],

                'image' => [

                    'label' => 'Obrázek',

                ],

            ],

        ],

        'notifications' => [

            'conversation-created-title' => 'Konverzace vytvořena',

        ],

    ],

    'empty-state' => [

        'heading' => 'Žádné konverzace nenalezeny',

        'description' => 'Zatím se neúčastníte žádné konverzace.',

    ],
];
