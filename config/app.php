<?php

declare(strict_types=1);

/**
 * Zentrale Projektkonfiguration.
 *
 * Diese Datei gibt bewusst nur ein einfaches Array zurück,
 * damit der Service ohne zusätzliche Bibliotheken lauffähig bleibt.
 *
 * @return array<string, array<string, string>>
 */
return [
    'app' => [
        'service_name' => 'health',
        'timezone' => 'Europe/Berlin',
    ],
];
