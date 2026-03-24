<?php

declare(strict_types=1);

namespace Sasd\Health\Controller;

use Sasd\Health\Http\JsonResponse;
use Sasd\Health\Service\HealthService;

/**
 * Controller für den einzigen Endpoint dieses Services.
 *
 * Aufgabe des Controllers:
 * - Daten vom Service holen
 * - in ein HTTP/JSON-Format überführen
 */
final class HealthController
{
    /**
     * Fachlogik des Health-Endpoints.
     */
    private HealthService $healthService;

    /**
     * Logischer Service-Name für die Antwort.
     */
    private string $serviceName;

    /**
     * @param HealthService $healthService Service für Zeit- und Datumsdaten.
     * @param string $serviceName Anzeigename des Services.
     */
    public function __construct(HealthService $healthService, string $serviceName)
    {
        $this->healthService = $healthService;
        $this->serviceName = $serviceName;
    }

    /**
     * Liefert die Health-Informationen als JSON-Antwort zurück.
     */
    public function show(): JsonResponse
    {
        $data = $this->healthService->getCurrentDateTimePayload();

        return JsonResponse::ok([
            'status' => 'ok',
            'service' => $this->serviceName,
            'data' => $data,
        ]);
    }
}
