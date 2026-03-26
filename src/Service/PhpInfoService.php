<?php

declare(strict_types=1);

namespace Sasd\Health\Service;

/**
 * Erzeugt Metadaten und HTML-Ausgabe für den phpinfo()-Endpunkt.
 *
 * Wichtiger Sicherheitshinweis:
 * phpinfo() zeigt sehr viele Informationen über die PHP-Umgebung,
 * geladene Erweiterungen und Konfigurationen. Deshalb kann der Endpunkt
 * per Konfiguration ein- und ausgeschaltet werden.
 */
final class PhpInfoService
{
    /**
     * Gibt an, ob der Endpunkt aktiviert ist.
     */
    private bool $enabled;

    /**
     * Relativer Pfad des Endpunkts, z. B. phpinfo.
     */
    private string $routePath;

    /**
     * @param bool $enabled Schaltet den Endpunkt ein oder aus.
     * @param string $routePath Relativer Pfad des Endpunkts.
     */
    public function __construct(bool $enabled, string $routePath)
    {
        $this->enabled = $enabled;
        $this->routePath = trim($routePath, '/');
    }

    /**
     * Gibt zurück, ob der phpinfo()-Endpunkt aktiv ist.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Liefert Metadaten über den phpinfo()-Endpunkt für die JSON-Antwort.
     *
     * @return array<string, bool|string>
     */
    public function getEndpointMetadata(): array
    {
        return [
            'path' => '/' . $this->routePath,
            'enabled' => $this->enabled,
            'description' => 'Liefert die HTML-Ausgabe von phpinfo().',
        ];
    }

    /**
     * Erzeugt ein komplettes HTML-Dokument mit der Ausgabe von phpinfo().
     *
     * Die Ausgabe von phpinfo() wird per Output Buffering abgefangen,
     * damit wir sie kontrolliert als Response-Körper zurückgeben können.
     */
    public function generatePhpInfoDocument(): string
    {
        ob_start();
        phpinfo();
        $phpInfoOutput = (string) ob_get_clean();

        return $phpInfoOutput;
    }
}
