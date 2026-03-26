<?php

declare(strict_types=1);

namespace Sasd\Health\Http;

/**
 * Repräsentiert eine eingehende HTTP-Anfrage in vereinfachter Form.
 *
 * Für diesen Health-Service benötigen wir nur wenige Informationen:
 * - HTTP-Methode
 * - Request-Pfad
 * - Script-Name
 * - Basis-Pfad der Anwendung
 */
final class Request
{
    /**
     * HTTP-Methode in Großbuchstaben, z. B. GET oder HEAD.
     */
    private string $method;

    /**
     * Reiner Request-Pfad ohne Query-String.
     */
    private string $path;

    /**
     * Script-Name laut Serverumgebung, z. B. /health/index.php.
     */
    private string $scriptName;

    /**
     * Basis-Pfad der Anwendung, z. B. /health.
     */
    private string $basePath;

    /**
     * @param string $method HTTP-Methode.
     * @param string $path Aufgerufener Pfad.
     * @param string $scriptName Server-Variable SCRIPT_NAME.
     * @param string $basePath Basis-Pfad des Services.
     */
    public function __construct(string $method, string $path, string $scriptName, string $basePath)
    {
        $this->method = strtoupper($method);
        $this->path = $this->normalizePath($path);
        $this->scriptName = $this->normalizePath($scriptName);
        $this->basePath = $this->normalizeBasePath($basePath);
    }

    /**
     * Erzeugt ein Request-Objekt aus den PHP-Servervariablen.
     *
     * @param array<string, mixed> $server Server-Parameter aus $_SERVER.
     */
    public static function fromServer(array $server): self
    {
        $method = (string) ($server['REQUEST_METHOD'] ?? 'GET');
        $requestUri = (string) ($server['REQUEST_URI'] ?? '/');
        $scriptName = (string) ($server['SCRIPT_NAME'] ?? '/index.php');

        $parsedPath = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : '/';
        $basePath = dirname($scriptName);

        return new self($method, $path, $scriptName, $basePath);
    }

    /**
     * Gibt die HTTP-Methode zurück.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Prüft, ob die aktuelle HTTP-Methode erlaubt ist.
     *
     * @param array<int, string> $allowedMethods Liste erlaubter Methoden.
     */
    public function isMethodAllowed(array $allowedMethods): bool
    {
        return in_array($this->method, $allowedMethods, true);
    }

    /**
     * Prüft, ob die Anfrage genau auf die Basis-Route des Services zeigt.
     *
     * Unterstützte Varianten:
     * - /health
     * - /health/
     * - /health/index.php
     *
     * Falls der Service direkt auf der Domainwurzel liegen würde,
     * werden zusätzlich / und /index.php akzeptiert.
     */
    public function matchesBaseRoute(): bool
    {
        return in_array($this->path, $this->buildRouteVariants(''), true);
    }

    /**
     * Prüft, ob die Anfrage auf eine Route relativ zum Service-Basisordner zeigt.
     *
     * Beispiel:
     * - Basis-Pfad: /health
     * - Relativer Pfad: phpinfo
     * - Treffer: /health/phpinfo oder /health/phpinfo/
     *
     * @param string $relativePath Relativer Pfad innerhalb des Service-Ordners.
     */
    public function matchesRelativeRoute(string $relativePath): bool
    {
        return in_array($this->path, $this->buildRouteVariants($relativePath), true);
    }

    /**
     * Erzeugt zulässige Pfadvarianten für die Basisroute oder eine Unterroute.
     *
     * @param string $relativePath Relativer Pfad innerhalb des Service-Ordners.
     *
     * @return array<int, string>
     */
    private function buildRouteVariants(string $relativePath): array
    {
        $normalizedRelativePath = trim($relativePath, '/');
        $routePath = $this->basePath;

        if ($normalizedRelativePath !== '') {
            $routePath .= '/' . $normalizedRelativePath;
        }

        $routePath = $this->normalizePath($routePath === '' ? '/' : $routePath);
        $variants = [$routePath];

        if ($routePath !== '/') {
            $variants[] = $this->normalizePath($routePath . '/');
        }

        if ($normalizedRelativePath === '') {
            if ($this->basePath === '') {
                $variants[] = '/index.php';
            } else {
                $variants[] = $this->scriptName;
            }
        }

        return array_values(array_unique(array_map([$this, 'normalizePath'], $variants)));
    }

    /**
     * Vereinheitlicht einen Pfad.
     *
     * Beispiele:
     * - leer -> /
     * - /health/ -> /health
     * - / -> /
     */
    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        $normalized = '/' . ltrim($path, '/');

        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized;
    }

    /**
     * Vereinheitlicht den Basis-Pfad.
     */
    private function normalizeBasePath(string $basePath): string
    {
        $normalized = $this->normalizePath($basePath);

        return $normalized === '/' ? '' : $normalized;
    }
}
