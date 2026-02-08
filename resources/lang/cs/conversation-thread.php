<?php

declare(strict_types=1);

return [

    'empty-state' => [

        'heading' => 'Zatím tu nejsou žádné zprávy',

    ],

    'placeholder' => 'Zpráva...',

    'upload-modal' => [

        'heading' => 'Přetáhněte přílohy sem',

        'description' => 'Pusťte přílohy pro přidání ke zprávě.',

        'file-attachments-accepted-file-types-validation-message' => 'Nahrané soubory musí být typu: :values.',

        'file-attachments-max-size-validation-message' => 'Nahrané soubory nesmí být větší než :max kilobajtů.',

        'max-file-attachments-validation-message' => '{1} Můžete nahrát maximálně :count soubor.|[2,4] Můžete nahrát maximálně :count soubory.|[5,*] Můžete nahrát maximálně :count souborů.',

    ],

    'validation' => [

        'message-required' => 'Prosím zadejte zprávu nebo přiložte soubor.'

    ],

    'actions' => [

        'manage' => [

            'label' => 'Spravovat',

            'modal-heading' => 'Spravovat :name',

            'modal-submit-action-label' => 'Uložit',

            'notifications' => [

                'conversation-updated-title' => 'Konverzace aktualizována'

            ]

        ],

        'delete' => [

        ]

    ],

    'message-actions' => [

        'delete-label' => 'Odstranit',

        'edit-message' => [

            'label' => 'Upravit',

        ],

    ],

    'new-messages-divider-content' => [

        'label' => '{1} :count nová zpráva|[2,4] :count nové zprávy|[5,*] :count nových zpráv',

    ],

    'message-divider-content' => [

        'today' => 'Dnes',

        'yesterday' => 'Včera',

    ],

    'footer-actions' => [

        'upload-attachment-label' => 'Nahrát přílohu',

        'send-message-label' => 'Poslat zprávu',

    ],

    'read-receipt' => [

        'seen' => 'Zobrazeno',

        'seen-by-one' => 'Zobrazeno uživatelem :name',

        'seen-by-two' => 'Zobrazeno uživateli :firstName a :secondName',

        'seen-by-three' => 'Zobrazeno uživateli :firstName, :secondName a :thirdName',

        'seen-by-many-shortened' => 'Zobrazeno uživateli :firstName, :secondName, :thirdName a :othersCount dalších',

        'seen-by-everyone' => 'Zobrazeno všemi uživateli',

        'seen-by-many-full' => 'Zobrazeno uživateli :names a :lastName',

    ],

    'typing-indicator' => [

        'single' => '{singleName} píše...',

        'double' => '{firstName} a {secondName} píší...',

        'multiple' => '{firstName}, {secondName} a {count} {others} píší...',

        'other' => 'další',

        'others' => 'další',

    ],

    'attachments' => [

        'remove-button-label' => 'Odstranit',

        'mime-type' => [

            'image' => 'Obrázek',

            'audio' => 'Audio',

            'video' => 'Video',

            'pdf' => 'PDF',

            'document' => 'Dokument',

            'spreadsheet' => 'Tabulka',

        ],

    ],
];
