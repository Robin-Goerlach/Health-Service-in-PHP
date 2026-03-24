<?php

declare(strict_types=1);

namespace Sasd\Health;

use InvalidArgumentException;
use Sasd\Health\Controller\HealthController;
use Sasd\Health\Http\JsonResponse;
use Sasd\Health\Http\Request;
use Sasd\Health\Service\HealthService;
use Throwable;

/**
 * Startet die Anwendung, lädt die Konfiguration und steuert die Anfrageverarbeitung.
 *
 * Für dieses kleine Projekt übernimmt die Klasse bewusst drei klar begrenzte Aufgaben:
 * 1. Konfiguration laden
 * 2. einfachen PSR-4-artigen Autoloader registrieren
 * 3. Anfrage an Controller und Service weiterreichen
 */
final class Bootstrap
{
    /**
     * Physischer Projektpfad des Services.
     */
    private string $projectRoot;

    /**
     * Geladene Anwendungskonfiguration.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param string $projectRoot Absoluter Pfad des Projektwurzelverzeichnisses.
     *
     * @throws InvalidArgumentException Wenn die Konfiguration ungültig ist.
     */
    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

        $this->registerAutoloader();
        $this->config = $this->loadConfig();
        $this->applyRuntimeConfiguration();
    }

    /**
     * Verarbeitet die eingehende HTTP-Anfrage und liefert eine JSON-Antwort zurück.
     *
     * @param array<string, mixed> $server Server-Parameter aus $_SERVER.
     */
    public function handle(array $server): JsonResponse
    {
        try {
            $request = Request::fromServer($server);
            $allowedMethods = ['GET', 'HEAD', 'OPTIONS'];

            if (!$request->matchesBaseRoute()) {
                return JsonResponse::error(
                    404,
                    'Not Found',
                    'Die angeforderte Ressource wurde nicht gefunden.'
                );
            }

            if ($request->getMethod() === 'OPTIONS') {
                return JsonResponse::noContent([
                    'Allow' => implode(', ', $allowedMethods),
                ]);
            }

            if (!$request->isMethodAllowed(['GET', 'HEAD'])) {
                return JsonResponse::error(
                    405,
                    'Method Not Allowed',
                    'Für diese Ressource sind nur GET und HEAD erlaubt.',
                    [
                        'Allow' => implode(', ', $allowedMethods),
                    ]
                );
            }

            $healthService = new HealthService((string) $this->config['app']['timezone']);
            $healthController = new HealthController($healthService, (string) $this->config['app']['service_name']);

            return $healthController->show();
        } catch (Throwable $exception) {
            // Anwendungsfehler werden hier in eine stabile API-Antwort übersetzt.
            return JsonResponse::error(
                500,
                'Internal Server Error',
                'Die Anfrage konnte serverseitig nicht verarbeitet werden.'
            );
        }
    }

    /**
     * Lädt die Projektkonfiguration aus der Datei config/app.php.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException Wenn die Datei fehlt oder kein Array zurückliefert.
     */
    private function loadConfig(): array
    {
        $configFile = $this->projectRoot . '/config/app.php';

        if (!is_file($configFile)) {
            throw new InvalidArgumentException('Die Konfigurationsdatei config/app.php wurde nicht gefunden.');
        }

        $config = require $configFile;

        if (!is_array($config)) {
            throw new InvalidArgumentException('Die Konfigurationsdatei muss ein Array zurückgeben.');
        }

        return $config;
    }

    /**
     * Wendet einfache Runtime-Einstellungen aus der Konfiguration an.
     *
     * @throws InvalidArgumentException Wenn die konfigurierte Zeitzone ungültig ist.
     */
    private function applyRuntimeConfiguration(): void
    {
        $timezone = (string) ($this->config['app']['timezone'] ?? 'UTC');

        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException(sprintf('Die konfigurierte Zeitzone "%s" ist ungültig.', $timezone));
        }

        // Dadurch arbeiten alle Datums- und Zeitfunktionen der Anwendung konsistent.
        date_default_timezone_set($timezone);
    }

    /**
     * Registriert einen kleinen projektinternen Autoloader.
     *
     * Es wird bewusst kein Composer vorausgesetzt, damit die Anwendung
     * auf einfachem Webhosting möglichst leicht deploybar bleibt.
     */
    private function registerAutoloader(): void
    {
        spl_autoload_register(function (string $className): void {
            $namespacePrefix = 'Sasd\\Health\\';

            if (strncmp($className, $namespacePrefix, strlen($namespacePrefix)) !== 0) {
                return;
            }

            $relativeClass = substr($className, strlen($namespacePrefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            $file = $this->projectRoot . '/src/' . $relativePath;

            if (is_file($file)) {
                require_once $file;
            }
        });
    }
}
