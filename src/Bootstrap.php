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
 * Bootstrapt die Anwendung und verarbeitet eingehende HTTP-Anfragen.
 */
final class Bootstrap
{
    /**
     * Absoluter Pfad des Projektwurzelverzeichnisses.
     *
     * @var string
     */
    private string $projectRoot;

    /**
     * Geladene Projektkonfiguration.
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
     * - /time -> JSON mit Datum, Uhrzeit und Zeitzone
     * - /phpinfo -> HTML-Ausgabe von phpinfo()
     *
     * @param array $server Server-Parameter aus $_SERVER.
     *
     * @return ResponseInterface
     */
    public function handle(array $server): ResponseInterface
    {
        try {
            $request = Request::fromServer($server);
            $allowedMethods = ['GET', 'HEAD', 'OPTIONS'];

            $timeRoute = (string) ($this->config['app']['time_route'] ?? 'time');
            $phpInfoRoute = (string) ($this->config['app']['phpinfo_route'] ?? 'phpinfo');

            if ($request->matchesBaseRoute()) {
                return $this->handleBaseRoute($request, $allowedMethods);
            }

            if ($request->matchesRelativeRoute($timeRoute)) {
                return $this->handleTimeRoute($request, $allowedMethods);
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
     *
     * @return ResponseInterface
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

        $healthController = $this->buildHealthController();

        return $healthController->showHealth();
    }

    /**
     * Verarbeitet die Zusatzroute /time.
     *
     * @param Request $request Vereinfachte HTTP-Anfrage.
     * @param array<int, string> $allowedMethods Erlaubte HTTP-Methoden.
     *
     * @return ResponseInterface
     */
    private function handleTimeRoute(Request $request, array $allowedMethods): ResponseInterface
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

        $healthController = $this->buildHealthController();

        return $healthController->showTime();
    }

    /**
     * Verarbeitet die Zusatzroute für phpinfo().
     *
     * @param Request $request Vereinfachte HTTP-Anfrage.
     * @param array<int, string> $allowedMethods Erlaubte HTTP-Methoden.
     *
     * @return ResponseInterface
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

        $healthController = $this->buildHealthController();

        return $healthController->showPhpInfo();
    }

    /**
     * Baut den Controller mit seinen benötigten Services auf.
     *
     * @return HealthController
     */
    private function buildHealthController(): HealthController
    {
        $healthService = new HealthService((string) $this->config['app']['timezone']);
        $phpInfoService = new PhpInfoService(
            (bool) ($this->config['app']['phpinfo_enabled'] ?? false),
            (string) ($this->config['app']['phpinfo_route'] ?? 'phpinfo')
        );

        return new HealthController(
            $healthService,
            $phpInfoService,
            (string) $this->config['app']['service_name']
        );
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
            throw new InvalidArgumentException(sprintf(
                'Die konfigurierte Zeitzone "%s" ist ungültig.',
                $timezone
            ));
        }

        // Dadurch arbeiten alle Datums- und Zeitfunktionen der Anwendung konsistent.
        date_default_timezone_set($timezone);
    }

    /**
     * Registriert einen kleinen projektinternen Autoloader.
     *
     * Es wird bewusst kein Composer vorausgesetzt, damit die Anwendung
     * auf einfachem Webhosting möglichst leicht deploybar bleibt.
     *
     * @return void
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