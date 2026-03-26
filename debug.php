<?php

header('Content-Type: text/plain; charset=UTF-8');

$keys = [
    'DOCUMENT_ROOT',
    'REQUEST_URI',
    'SCRIPT_NAME',
    'SCRIPT_FILENAME',
    'PHP_SELF',
    'PATH_INFO',
    'REDIRECT_URL',
    'REDIRECT_STATUS',
    'HTTP_HOST',
    'REQUEST_METHOD',
];

foreach ($keys as $key) {
    echo $key . '=' . ($_SERVER[$key] ?? '[not set]') . PHP_EOL;
}
