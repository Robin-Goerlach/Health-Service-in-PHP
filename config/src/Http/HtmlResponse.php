<?php

declare(strict_types=1);

namespace Sasd\Health\Http;

/**
 * Kapselt eine HTML-Antwort.
 *
 * Die Klasse wird für den phpinfo()-Endpunkt genutzt,
 * weil phpinfo() keine JSON-, sondern HTML-Ausgabe erzeugt.
 */
final class HtmlResponse implements ResponseInterface
{
    /**
     * HTTP-Statuscode der Antwort.
     */
    private int $statusCode;

    /**
     * HTML-Body der Antwort.
     */
    private string $body;

    /**
     * HTTP-Header der Antwort.
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @param int $statusCode HTTP-Statuscode.
     * @param string $body HTML-Inhalt.
     * @param array<string, string> $headers Zusätzliche Header.
     */
    public function __construct(int $statusCode, string $body, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers + [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    /**
     * Erzeugt eine erfolgreiche 200-HTML-Antwort.
     *
     * @param string $body HTML-Inhalt.
     * @param array<string, string> $headers Zusätzliche Header.
     */
    public static function ok(string $body, array $headers = []): self
    {
        return new self(200, $body, $headers);
    }

    /**
     * Erzeugt eine kleine HTML-Fehlerseite.
     *
     * @param int $statusCode HTTP-Statuscode.
     * @param string $title Kurzer Seitentitel.
     * @param string $message Lesbare Fehlermeldung.
     */
    public static function errorPage(int $statusCode, string $title, string $message): self
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $body = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle}</title>
</head>
<body>
    <h1>{$safeTitle}</h1>
    <p>{$safeMessage}</p>
</body>
</html>
HTML;

        return new self($statusCode, $body);
    }

    /**
     * Sendet Statuscode, Header und HTML-Ausgabe an den Client.
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

        if (strtoupper($requestMethod) === 'HEAD') {
            return;
        }

        echo $this->body;
    }
}
