<?php

declare(strict_types=1);

namespace Sasd\Health\Controller;

use Sasd\Health\Http\HtmlResponse;
use Sasd\Health\Http\JsonResponse;
use Sasd\Health\Service\HealthService;
use Sasd\Health\Service\PhpInfoService;

/**
 * Controller für die Endpunkte dieses kleinen Services.
 *
 * Aufgabe des Controllers:
 * - Daten von Services holen
 * - sie in passende HTTP-Antworten umwandeln
 *
 * Der Controller kennt dabei zwei fachliche Ausgaben:
 * - Health-JSON
 * - phpinfo()-HTML
 */
final class HealthController
{
    /**
     * Fachlogik für den Health-Endpunkt.
     */
    private HealthService $healthService;

    /**
     * Fachlogik für die phpinfo()-Ausgabe.
     */
    private PhpInfoService $phpInfoService;

    /**
     * Logischer Service-Name für die JSON-Antwort.
     */
    private string $serviceName;

    /**
     * @param HealthService $healthService Service für Zeit- und Datumsdaten.
     * @param PhpInfoService $phpInfoService Service für die phpinfo()-Ausgabe.
     * @param string $serviceName Anzeigename des Services.
     */
    public function __construct(
        HealthService $healthService,
        PhpInfoService $phpInfoService,
        string $serviceName
    ) {
        $this->healthService = $healthService;
        $this->phpInfoService = $phpInfoService;
        $this->serviceName = $serviceName;
    }

    /**
     * Liefert die Health-Informationen als JSON-Antwort zurück.
     */
    public function showHealth(): JsonResponse
    {
        $data = $this->healthService->getCurrentDateTimePayload();
        $phpInfoMeta = $this->phpInfoService->getEndpointMetadata();

        return JsonResponse::ok([
            'status' => 'ok',
            'service' => $this->serviceName,
            'data' => $data,
            'endpoints' => [
                'health' => [
                    'path' => '/',
                    'description' => 'Liefert Datum, Uhrzeit und Zeitzone als JSON.',
                ],
                'phpinfo' => $phpInfoMeta,
            ],
        ]);
    }

    /**
     * Liefert die Ausgabe von phpinfo() als HTML-Antwort zurück.
     *
     * Falls der Endpunkt per Konfiguration deaktiviert wurde,
     * liefert der Service stattdessen eine 403-Fehlerantwort als HTML.
     */
    public function showPhpInfo(): HtmlResponse
    {
        if (!$this->phpInfoService->isEnabled()) {
            return HtmlResponse::errorPage(
                403,
                'Forbidden',
                'Der phpinfo-Endpunkt ist in der Konfiguration deaktiviert.'
            );
        }

        $document = $this->phpInfoService->generatePhpInfoDocument();

        return HtmlResponse::ok($document);
    }
}
