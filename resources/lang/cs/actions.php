<?php

declare(strict_types=1);

return [

    'schema' => [

        'participants' => [

            'label' => 'Ostatní účastníci',

            'validation' => [

                'direct-conversation-exists' => 'S tímto uživatelem již máte konverzaci.'

            ],

            'participation' => [

                'owner' => 'Vlastník',

                'default' => 'Účastník'

            ]

        ],

        'participations' => [

            'label' => 'Účastníci',

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

    'create' => [

        'label' => 'Vytvořit',

        'modal-heading' => 'Vytvořit novou konverzaci',

        'modal-submit-action-label' => 'Vytvořit',

        'success-notification-title' => 'Konverzace vytvořena'

    ],

    'manage' => [

        'label' => 'Spravovat',

        'modal-heading-view' => 'Zobrazit konverzaci',

        'modal-heading-edit' => 'Upravit konverzaci',

        'modal-submit-action-label' => 'Uložit',

        'success-notification-title' => 'Konverzace aktualizována',

        'advanced-actions' => [

            'label' => 'Pokročilé akce',

            'transfer-conversation-text' => 'Převést vlastnictví této konverzace na jiného účastníka.',

            'leave-conversation-text' => 'Odebrat se z této konverzace. Již nebudete dostávat zprávy.',

            'delete-conversation-text' => 'Trvale odstranit tuto konverzaci pro všechny účastníky. Tuto akci nelze vrátit zpět.',

        ],
    ],

    'transfer' => [

        'label' => 'Převést',

        'modal-heading' => 'Převést vlastnictví konverzace?',

        'modal-heading-single-participant' => 'Převést vlastnictví na :name?',

        'modal-submit-action-label' => 'Převést',

        'success-notification-title' => 'Vlastnictví konverzace převedeno',

    ],

    'leave' => [

        'label' => 'Opustit',

        'modal-heading' => 'Opustit konverzaci?',

        'modal-submit-action-label' => 'Opustit',

        'success-notification-title' => 'Konverzace opuštěna',

    ],

    'delete' => [

        'label' => 'Odstranit',

        'modal-heading' => 'Odstranit konverzaci?',

        'modal-submit-action-label' => 'Odstranit',

        'success-notification-title' => 'Konverzace odstraněna',

    ],

];
