<?php

declare(strict_types=1);

/**
 * Zentrale Projektkonfiguration.
 *
 * Diese Datei gibt bewusst nur ein einfaches Array zurück,
 * damit der Service ohne zusätzliche Bibliotheken lauffähig bleibt.
 *
 * Sicherheits-Hinweis:
 * Der phpinfo()-Endpunkt zeigt viele Details über deine PHP-Umgebung.
 * Für produktive öffentliche Systeme solltest du gut überlegen,
 * ob der Endpunkt dauerhaft aktiviert sein soll.
 *
 * @return array<string, array<string, bool|string>>
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
    ],
];
