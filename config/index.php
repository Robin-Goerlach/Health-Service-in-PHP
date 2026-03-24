<?php

declare(strict_types=1);

/**
 * Einstiegspunkt des Health-Services.
 *
 * Die Datei liegt bewusst direkt im physischen Ordner "health",
 * damit sie zur gewünschten Hosting-Struktur unter api.sasd.de/health passt.
 *
 * Der Einstiegspunkt kennt absichtlich nur zwei Dinge:
 * - Bootstrap laden
 * - die erzeugte HTTP-Antwort senden
 *
 * Dadurch bleibt die Datei sehr klein und die eigentliche Logik
 * liegt in testbareren Klassen unterhalb von src/.
 */

use Sasd\Health\Bootstrap;
use Sasd\Health\Http\JsonResponse;
use Sasd\Health\Http\ResponseInterface;

require_once __DIR__ . '/src/Bootstrap.php';
require_once __DIR__ . '/src/Http/ResponseInterface.php';
require_once __DIR__ . '/src/Http/JsonResponse.php';

try {
    // Bootstrap lädt Konfiguration, registriert den Autoloader
    // und übergibt die HTTP-Anfrage an das Routing der Anwendung.
    $bootstrap = new Bootstrap(__DIR__);
    $response = $bootstrap->handle($_SERVER);
} catch (Throwable $exception) {
    // Selbst bei Startfehlern wollen wir keine rohe PHP-Fehlerseite,
    // sondern eine saubere und kontrollierte API-Antwort zurückgeben.
    $response = JsonResponse::error(
        500,
        'Internal Server Error',
        'Beim Verarbeiten der Anfrage ist ein unerwarteter Fehler aufgetreten.'
    );
}

if ($response instanceof ResponseInterface) {
    $response->send($_SERVER['REQUEST_METHOD'] ?? 'GET');
}
