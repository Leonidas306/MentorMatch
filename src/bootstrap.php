<?php
declare(strict_types=1);

// Configuración de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();
}

date_default_timezone_set('America/Mexico_City');

define('BASE_PATH', dirname(__DIR__));
define('DB_PATH', BASE_PATH . '/storage/mentormatch.sqlite');
define('SCHEMA_PATH', BASE_PATH . '/database/schema.sql');
define('SEED_PATH', BASE_PATH . '/database/seed.sql');

/**
 * Escapa cadenas para salida segura en HTML (Prevención XSS)
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Genera o recupera el token CSRF de la sesión actual
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF enviado por POST sea válido
 */
function verify_csrf(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtiene la conexión PDO a SQLite e inicializa automáticamente
 * las tablas y datos semilla si el archivo aún no existe.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $storageDir = dirname(DB_PATH);
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0775, true);
    }

    $needsInit = !file_exists(DB_PATH) || filesize(DB_PATH) === 0;

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON;');

    if ($needsInit) {
        $schemaSql = file_get_contents(SCHEMA_PATH);
        if ($schemaSql !== false) {
            $pdo->exec($schemaSql);
        }

        $seedSql = file_get_contents(SEED_PATH);
        if ($seedSql !== false) {
            $demoHash = password_hash('demo1234', PASSWORD_DEFAULT);
            $seedSqlWithHash = str_replace('__DEMO_HASH__', $demoHash, $seedSql);
            $pdo->exec($seedSqlWithHash);
        }
    }

    return $pdo;
}

// Inicializar base de datos y cargar módulos principales
db();
require_once BASE_PATH . '/src/repository.php';
require_once BASE_PATH . '/src/auth.php';
require_once BASE_PATH . '/src/layout.php';
