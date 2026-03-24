<?php

declare(strict_types=1);

namespace Sasd\Health\Service;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Liefert die eigentlichen Health-Daten des Services.
 *
 * Im aktuellen Stand bedeutet "Health":
 * - aktuelles Datum
 * - aktuelle Uhrzeit
 * - Zeitzone
 * - ISO-/Unix-Darstellung
 *
 * Der Service ist bewusst klein gehalten, damit er später leicht
 * um weitere Prüfungen erweitert werden kann, z. B. Datenbankstatus,
 * Build-Version oder externe Abhängigkeiten.
 */
final class HealthService
{
    /**
     * Name der zu verwendenden Zeitzone, z. B. Europe/Berlin.
     */
    private string $timezoneName;

    /**
     * @param string $timezoneName IANA-Zeitzone.
     *
     * @throws InvalidArgumentException Wenn die Zeitzone ungültig ist.
     */
    public function __construct(string $timezoneName)
    {
        if (!in_array($timezoneName, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException(sprintf('Ungültige Zeitzone: %s', $timezoneName));
        }

        $this->timezoneName = $timezoneName;
    }

    /**
     * Erzeugt die fachlichen Nutzdaten des Health-Endpoints.
     *
     * @return array<string, int|string>
     */
    public function getCurrentDateTimePayload(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone($this->timezoneName));

        return [
            // ISO-8601 / ATOM ist maschinenfreundlich und für APIs gut geeignet.
            'currentDateTime' => $now->format(DateTimeInterface::ATOM),
            'currentDate' => $now->format('Y-m-d'),
            'currentTime' => $now->format('H:i:s'),
            'timezone' => $now->getTimezone()->getName(),
            'timezoneOffset' => $now->format('P'),
            'timezoneAbbreviation' => $now->format('T'),
            'unixTimestamp' => $now->getTimestamp(),
        ];
    }
}
