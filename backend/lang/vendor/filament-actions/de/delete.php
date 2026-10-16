<?php

// Filament siezt im Deutschen. Hier nur die Schlüssel, die das CMS zeigt, in du-Form (docs/design/Sprache.md).
// Laravel ergänzt den Rest aus vendor/filament (Schlüssel für Schlüssel).

return [
    'multiple' => [
        'notifications' => [
            'deleted_partial' => [
                'missing_authorization_failure_message' => 'Du darfst :count nicht löschen.',
            ],
            'deleted_none' => [
                'missing_authorization_failure_message' => 'Du darfst :count nicht löschen.',
            ],
        ],
    ],
];
