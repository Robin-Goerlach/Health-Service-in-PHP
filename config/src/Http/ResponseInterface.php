<?php

declare(strict_types=1);

namespace Sasd\Health\Http;

/**
 * Gemeinsame Schnittstelle für HTTP-Antworten.
 *
 * Durch dieses Interface kann der Bootstrap unabhängig davon arbeiten,
 * ob eine Route JSON, HTML oder später vielleicht Text oder XML liefert.
 */
interface ResponseInterface
{
    /**
     * Sendet Statuscode, Header und gegebenenfalls den Body an den Client.
     *
     * @param string $requestMethod Ursprüngliche HTTP-Methode, z. B. GET oder HEAD.
     */
    public function send(string $requestMethod = 'GET'): void;
}
