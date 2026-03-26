<?php

declare(strict_types=1);

namespace Sasd\Health;

use DateTimeZone;
use InvalidArgumentException;
use Sasd\Health\Controller\HealthController;
use Sasd\Health\Http\JsonResponse;
use Sasd\Health\Http\Request;
use Sasd\Health\Http\ResponseInterface;
use Sasd\Health\Service\HealthService;
use Sasd\Health\Service\PhpInfoService;
use Throwable;

/**
 * Startet die Anwendung, lädt die Konfiguration und steuert die Anfrageverarbeitung.
 *
 * Für dieses kleine Projekt übernimmt die Klasse bewusst mehrere klar begrenzte Aufgaben:
 * 1. Konfiguration laden
 * 2. projektinternen Autoloader registrieren
 * 3. Runtime-Konfiguration anwenden
 * 4. HTTP-Anfrage an den passenden Controller-Zweig weiterleiten
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
     * Verarbeitet die eingehende HTTP-Anfrage und liefert eine HTTP-Antwort zurück.
     *
     * Je nach Route wird entweder JSON oder HTML erzeugt:
     * - Basisroute des Ordners -> JSON-Health-Antwort
     * - /phpinfo -> HTML-Ausgabe von phpinfo()
     *
     * @param array<string, mixed> $server Server-Parameter aus $_SERVER.
     */
    public function handle(array $server): ResponseInterface
    {
        try {
            $request = Request::fromServer($server);
            $allowedMethods = ['GET', 'HEAD', 'OPTIONS'];
            $phpInfoRoute = (string) ($this->config['app']['phpinfo_route'] ?? 'phpinfo');

            if ($request->matchesBaseRoute()) {
                return $this->handleBaseRoute($request, $allowedMethods);
            }

            if ($request->matchesRelativeRoute($phpInfoRoute)) {
                return $this->handlePhpInfoRoute($request, $allowedMethods);
            }

            return JsonResponse::error(
                404,
                'Not Found',
                'Die angeforderte Ressource wurde nicht gefunden.'
            );
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
     * Verarbeitet die Basisroute des Services, also z. B. /health oder /health/.
     *
     * @param Request $request Vereinfachte HTTP-Anfrage.
     * @param array<int, string> $allowedMethods Erlaubte HTTP-Methoden.
     */
    private function handleBaseRoute(Request $request, array $allowedMethods): ResponseInterface
    {
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
        $phpInfoService = new PhpInfoService(
            (bool) ($this->config['app']['phpinfo_enabled'] ?? false),
            (string) ($this->config['app']['phpinfo_route'] ?? 'phpinfo')
        );

        $healthController = new HealthController(
            $healthService,
            $phpInfoService,
            (string) $this->config['app']['service_name']
        );

        return $healthController->showHealth();
    }

    /**
     * Verarbeitet die Zusatzroute für phpinfo().
     *
     * @param Request $request Vereinfachte HTTP-Anfrage.
     * @param array<int, string> $allowedMethods Erlaubte HTTP-Methoden.
     */
    private function handlePhpInfoRoute(Request $request, array $allowedMethods): ResponseInterface
    {
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
        $phpInfoService = new PhpInfoService(
            (bool) ($this->config['app']['phpinfo_enabled'] ?? false),
            (string) ($this->config['app']['phpinfo_route'] ?? 'phpinfo')
        );

        $healthController = new HealthController(
            $healthService,
            $phpInfoService,
            (string) $this->config['app']['service_name']
        );

        return $healthController->showPhpInfo();
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

        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
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
