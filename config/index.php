<?php

declare(strict_types=1);

/**
 * Einstiegspunkt des Health-Services.
 *
 * Die Datei liegt bewusst direkt im physischen Ordner "health",
 * damit sie zur gewünschten Hosting-Struktur unter api.sasd.de/health passt.
 */

use Sasd\Health\Bootstrap;
use Sasd\Health\Http\JsonResponse;

require_once __DIR__ . '/src/Bootstrap.php';
require_once __DIR__ . '/src/Http/JsonResponse.php';

try {
    // Die Bootstrap-Klasse lädt Konfiguration, registriert den Autoloader
    // und führt anschließend das Routing für diese kleine API aus.
    $bootstrap = new Bootstrap(__DIR__);
    $response = $bootstrap->handle($_SERVER);
} catch (Throwable $exception) {
    // Falls beim Starten der Anwendung etwas Unerwartetes passiert,
    // liefern wir trotzdem eine saubere JSON-Antwort statt einer PHP-Fehlerseite.
    $response = JsonResponse::error(
        500,
        'Internal Server Error',
        'Beim Verarbeiten der Anfrage ist ein unerwarteter Fehler aufgetreten.'
    );
}

$response->send($_SERVER['REQUEST_METHOD'] ?? 'GET');
