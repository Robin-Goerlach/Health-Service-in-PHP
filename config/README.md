# SASD Health API mit phpinfo-Endpunkt

Ein kleiner, sauber strukturierter PHP-REST-Service für deine IONOS-Ordnerstruktur unter:

- `http://api.sasd.de/health`

Zusätzlich gibt es jetzt einen zweiten Endpunkt für `phpinfo()`:

- `http://api.sasd.de/health/phpinfo`

## Ziel

Der Service liefert:

- auf der Basisroute JSON mit Datum, Uhrzeit und Zeitzone
- auf `/phpinfo` die HTML-Ausgabe von `phpinfo()`

## Struktur

Die Struktur ist absichtlich auf deine Hosting-Idee abgestimmt:

```text
api.sasd.de/
├── health/
│   ├── .htaccess
│   ├── index.php
│   ├── config/
│   │   └── app.php
│   └── src/
│       ├── Bootstrap.php
│       ├── Controller/
│       │   └── HealthController.php
│       ├── Http/
│       │   ├── HtmlResponse.php
│       │   ├── JsonResponse.php
│       │   ├── Request.php
│       │   └── ResponseInterface.php
│       └── Service/
│           ├── HealthService.php
│           └── PhpInfoService.php
└── taskhost/
    └── ...
```

Wichtig: `index.php` liegt **direkt** im physischen Ordner `health`, also **nicht** in einem zusätzlichen `public/`-Verzeichnis. Das ist hier absichtlich so, damit es zu deiner realen IONOS-Struktur passt.

## Endpunkte

### 1. Basisroute des Services

- `GET http://api.sasd.de/health`
- `GET http://api.sasd.de/health/`
- `GET http://api.sasd.de/health/index.php`

Beispielantwort:

```json
{
    "status": "ok",
    "service": "health",
    "data": {
        "currentDateTime": "2026-03-24T14:10:25+01:00",
        "currentDate": "2026-03-24",
        "currentTime": "14:10:25",
        "timezone": "Europe/Berlin",
        "timezoneOffset": "+01:00",
        "timezoneAbbreviation": "CET",
        "unixTimestamp": 1774357825
    },
    "endpoints": {
        "health": {
            "path": "/",
            "description": "Liefert Datum, Uhrzeit und Zeitzone als JSON."
        },
        "phpinfo": {
            "path": "/phpinfo",
            "enabled": true,
            "description": "Liefert die HTML-Ausgabe von phpinfo()."
        }
    }
}
```

### 2. phpinfo-Endpunkt

- `GET http://api.sasd.de/health/phpinfo`
- `GET http://api.sasd.de/health/phpinfo/`

Dieser Endpunkt liefert die HTML-Ausgabe von `phpinfo()` zurück.

## Deployment auf IONOS

1. Den kompletten Inhalt dieses Projekts in den physischen Ordner `health` unterhalb deiner Subdomain `api.sasd.de` kopieren.
2. Sicherstellen, dass PHP auf dem Webspace aktiviert ist.
3. Die URLs aufrufen:
   - `http://api.sasd.de/health`
   - `http://api.sasd.de/health/phpinfo`

## Sicherheitshinweis zu phpinfo()

`phpinfo()` ist praktisch zur Fehlersuche und zur Prüfung der Serverumgebung, zeigt aber sehr viele technische Details an. Deshalb wurde der Endpunkt konfigurierbar gemacht.

In `config/app.php` kannst du ihn ein- oder ausschalten:

```php
return [
    'app' => [
        'service_name' => 'health',
        'timezone' => 'Europe/Berlin',
        'phpinfo_enabled' => true,
        'phpinfo_route' => 'phpinfo',
    ],
];
```

Wenn du ihn deaktivierst:

```php
'phpinfo_enabled' => false,
```

liefert `http://api.sasd.de/health/phpinfo` eine `403 Forbidden`-Seite.

## Unterstützte Methoden

Für beide Routen gilt:

- `GET`
- `HEAD`
- `OPTIONS`

Andere Methoden liefern `405 Method Not Allowed`.

## Warum diese Erweiterung sauber ist

`phpinfo()` liefert HTML, nicht JSON. Deshalb wurde der Code bewusst erweitert statt verbogen:

- **JsonResponse** für API-Antworten
- **HtmlResponse** für HTML-Ausgaben
- **ResponseInterface** als gemeinsame Basis
- **PhpInfoService** für die Generierung und Konfiguration des phpinfo-Endpunkts

Damit bleibt der Code verständlich, erweiterbar und besser wartbar.
