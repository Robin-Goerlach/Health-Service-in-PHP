<?php

declare(strict_types=1);

namespace Sasd\Health\Controller;

use Sasd\Health\Http\HtmlResponse;
use Sasd\Health\Http\JsonResponse;
use Sasd\Health\Service\HealthService;
use Sasd\Health\Service\PhpInfoService;

/**
 * Controller für die öffentlich erreichbaren Endpunkte des Health-Services.
 */
final class HealthController
{
    /**
     * Fachservice für Datum, Uhrzeit und Zeitzone.
     *
     * @var HealthService
     */
    private HealthService $healthService;

    /**
     * Fachservice für phpinfo().
     *
     * @var PhpInfoService
     */
    private PhpInfoService $phpInfoService;

    /**
     * Anzeigename des Dienstes.
     *
     * @var string
     */
    private string $serviceName;

    /**
     * @param HealthService $healthService Fachservice für Zeitdaten.
     * @param PhpInfoService $phpInfoService Fachservice für phpinfo().
     * @param string $serviceName Anzeigename des Dienstes.
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
     *
     * @return JsonResponse
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
                'time' => [
                    'path' => '/time',
                    'description' => 'Liefert Datum, Uhrzeit und Zeitzone als reine JSON-Nutzdaten.',
                ],
                'phpinfo' => $phpInfoMeta,
            ],
        ]);
    }

    /**
     * Liefert Datum, Uhrzeit und Zeitzone als schlanke JSON-Antwort zurück.
     *
     * Dieser Endpunkt ist nützlich, wenn ein Client nur die eigentlichen
     * Zeitdaten benötigt und keine zusätzliche Endpunktbeschreibung.
     *
     * @return JsonResponse
     */
    public function showTime(): JsonResponse
    {
        return JsonResponse::ok([
            'status' => 'ok',
            'service' => $this->serviceName,
            'data' => $this->healthService->getCurrentDateTimePayload(),
        ]);
    }

    /**
     * Liefert die Ausgabe von phpinfo() als HTML-Antwort zurück.
     *
     * Falls der Endpunkt per Konfiguration deaktiviert wurde,
     * liefert der Service stattdessen eine 403-Fehlerantwort als HTML.
     *
     * @return HtmlResponse
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