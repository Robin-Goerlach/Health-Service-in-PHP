<?php

declare(strict_types=1);

namespace Sasd\Health\Http;

/**
 * Kapselt eine HTTP-JSON-Antwort.
 *
 * Die Klasse hält Statuscode, Nutzdaten und Header zusammen und sorgt
 * für eine einheitliche Ausgabe im API-Format.
 */
final class JsonResponse
{
    /**
     * HTTP-Statuscode der Antwort.
     */
    private int $statusCode;

    /**
     * JSON-Nutzdaten der Antwort.
     *
     * @var array<string, mixed>
     */
    private array $payload;

    /**
     * HTTP-Header der Antwort.
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @param int $statusCode HTTP-Statuscode.
     * @param array<string, mixed> $payload JSON-Daten.
     * @param array<string, string> $headers Zusätzliche Header.
     */
    public function __construct(int $statusCode, array $payload = [], array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->payload = $payload;
        $this->headers = $headers + [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    /**
     * Erzeugt eine erfolgreiche 200-JSON-Antwort.
     *
     * @param array<string, mixed> $payload JSON-Daten.
     * @param array<string, string> $headers Zusätzliche Header.
     */
    public static function ok(array $payload, array $headers = []): self
    {
        return new self(200, $payload, $headers);
    }

    /**
     * Erzeugt eine leere Antwort ohne Body, z. B. für OPTIONS.
     *
     * @param array<string, string> $headers Zusätzliche Header.
     */
    public static function noContent(array $headers = []): self
    {
        return new self(204, [], $headers);
    }

    /**
     * Erzeugt eine standardisierte Fehlermeldung als JSON.
     *
     * @param int $statusCode HTTP-Statuscode.
     * @param string $error Kurzer Fehlertext.
     * @param string $message Lesbare Fehlermeldung.
     * @param array<string, string> $headers Zusätzliche Header.
     */
    public static function error(int $statusCode, string $error, string $message, array $headers = []): self
    {
        return new self($statusCode, [
            'status' => 'error',
            'error' => $error,
            'message' => $message,
        ], $headers);
    }

    /**
     * Sendet Statuscode, Header und JSON-Ausgabe an den Client.
     *
     * Bei HEAD-Anfragen wird bewusst kein Body gesendet.
     *
     * @param string $requestMethod Ursprüngliche HTTP-Methode.
     */
    public function send(string $requestMethod = 'GET'): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        // 204-Antworten sowie HEAD-Requests enthalten bewusst keinen Body.
        if ($this->statusCode === 204 || strtoupper($requestMethod) === 'HEAD') {
            return;
        }

        $json = json_encode(
            $this->payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            // Notfall-Fallback, falls JSON-Encoding wider Erwarten scheitert.
            echo '{"status":"error","error":"Encoding Error","message":"Die JSON-Antwort konnte nicht erzeugt werden."}';
            return;
        }

        echo $json;
    }
}
