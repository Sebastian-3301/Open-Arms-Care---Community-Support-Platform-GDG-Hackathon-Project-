<?php
declare(strict_types=1);

/*
 * OPEN ARMS - INFINITYFREE DATABASE CONFIGURATION
 *
 * Get these values from:
 * InfinityFree Control Panel
 * -> MySQL Databases
 */

const DB_HOST = 'sql101.infinityfree.com';
const DB_PORT = '3306';

const DB_NAME = 'if0_42901405_OpenArmsDB';
const DB_USER = 'if0_42901405';

const DB_PASSWORD = 'ped0nXNSym2L';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST .
           ';port=' . DB_PORT .
           ';dbname=' . DB_NAME .
           ';charset=utf8mb4';

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    return $pdo;
}
