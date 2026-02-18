<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = app_env('DB_HOST', 'db');
    $port = app_env('DB_PORT', '3306');
    $database = app_env('DB_NAME', app_env('MYSQL_DATABASE', 'meine_db'));
    $user = app_env('DB_USER', app_env('MYSQL_USER', 'benutzer'));
    $password = app_env('DB_PASSWORD', app_env('MYSQL_PASSWORD', 'benutzerpasswort'));

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        http_response_code(500);
        echo '<h1>Datenbankverbindung fehlgeschlagen</h1>';
        echo '<p>Bitte Docker-Dienste und Zugangsdaten prüfen.</p>';
        if (app_env('APP_DEBUG', '1') === '1') {
            echo '<pre>' . e($exception->getMessage()) . '</pre>';
        }
        exit;
    }

    return $pdo;
}
