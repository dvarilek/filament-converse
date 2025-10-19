<?php

declare(strict_types=1);

return [

    'heading' => 'Konverzace',

    'search' => [

        'label' => 'Hledat',

        'placeholder' => 'Hledat',

    ],

    'actions' => [

        'create' => [

            'label' => 'Vytvořit',

            'modal-heading' => 'Vytvořit novou konverzaci',

            'modal-description' => 'Jaký typ konverzace chcete vytvořit?',

        ],

        'create-direct' => [

            'label' => 'Přímá',

            'modal-heading' => 'Vytvořit novou přímou konverzaci',

            'modal-submit-action-label' => 'Vytvořit',

            'schema' => [

                'participant' => [

                    'label' => 'Účastník',

                    'placeholder' => 'Vyberte účastníka',

                ],

            ],

        ],

        'create-group' => [

            'label' => 'Skupinová',

            'modal-heading' => 'Vytvořit novou skupinovou konverzaci',

            'modal-submit-action-label' => 'Vytvořit',

            'schema' => [

                'participant' => [

                    'label' => 'Účastníci',

                    'placeholder' => 'Vyberte účastníky',

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
