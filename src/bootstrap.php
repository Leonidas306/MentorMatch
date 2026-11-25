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
 * Detecta si el archivo SQLite existente es de una versión anterior del esquema.
 */
function esquema_bd_desactualizado(): bool
{
    try {
        $tmp = new PDO('sqlite:' . DB_PATH);
        $columnas = $tmp->query('PRAGMA table_info(usuarios)')->fetchAll(PDO::FETCH_COLUMN, 1);
        $tmp = null; // libera el archivo (importante en Windows antes de renombrarlo)
        return !in_array('nivel_educativo', $columnas, true);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Mensajes flash (aviso de una sola vez tras una acción). Tipos: 'exito' | 'error'.
 * (PAQ-8) Se muestran automáticamente en render_header().
 */
function flash_set(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Exige sesión iniciada; si no, manda al login con un aviso.
 */
function requerir_login(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        flash_set('error', 'Inicia sesión para continuar.');
        redirigir('login.php');
    }
    return $usuario;
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

    // CAMBIO (PAQ-1): si la BD local tiene el esquema anterior (sin "nivel_educativo"),
    // se respalda automáticamente como mentormatch.old-FECHA.sqlite y se crea una nueva.
    if (file_exists(DB_PATH) && filesize(DB_PATH) > 0 && esquema_bd_desactualizado()) {
        $respaldo = $storageDir . '/mentormatch.old-' . date('Ymd-His') . '.sqlite';
        if (!@rename(DB_PATH, $respaldo)) {
            http_response_code(500);
            exit('La base de datos local tiene el esquema anterior. Cierra el servidor, borra storage/mentormatch.sqlite y vuelve a abrir la aplicación.');
        }
    }

    $needsInit = !file_exists(DB_PATH) || filesize(DB_PATH) === 0;

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON;');

    // (PAQ-10) Si la BD ya existía pero todavía no tiene la tabla "resenas", se crea sin tocar los datos
    // (schema.sql usa CREATE TABLE IF NOT EXISTS). Las BD nuevas traen reseñas de demostración desde seed.sql.
    // Cambio v3: columna "nivel_otro" (texto libre cuando el usuario elige "Otro" como grado). Se agrega sin tocar datos.
    if (!$needsInit) {
        $colsUsuarios = $pdo->query('PRAGMA table_info(usuarios)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('nivel_otro', $colsUsuarios, true)) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN nivel_otro TEXT NOT NULL DEFAULT ''");
        }
    }

    if (!$needsInit) {
        $tieneResenas = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'resenas'")->fetchColumn();
        if ($tieneResenas === 0) {
            $schemaSql = file_get_contents(SCHEMA_PATH);
            if ($schemaSql !== false) {
                $pdo->exec($schemaSql);
            }
        }
    }

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
