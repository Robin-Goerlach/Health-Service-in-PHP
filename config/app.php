<?php

declare(strict_types=1);

/**
 * Zentrale Projektkonfiguration für den Health-Service.
 *
 * Die Datei bleibt bewusst klein und einfach, damit der Service
 * auf einfachem Webhosting leicht verständlich und gut pflegbar bleibt.
 *
 * @return array<string, array<string, mixed>>
 */
return [
    'app' => [
        'service_name' => 'health',
        'timezone' => 'Europe/Berlin',

        // Aktiviert oder deaktiviert den phpinfo()-Endpunkt.
        'phpinfo_enabled' => true,

        // Relativer Endpunkt innerhalb des Service-Ordners.
        // Beispiel: api.sasd.de/health/phpinfo
        'phpinfo_route' => 'phpinfo',

        // Relativer Endpunkt für die reine Zeit-/Datums-Ausgabe als JSON.
        // Beispiel: api.sasd.de/health/time
        'time_route' => 'time',
    ],
];