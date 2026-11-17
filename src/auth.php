<?php
declare(strict_types=1);

/**
 * Devuelve el arreglo del usuario autenticado en la sesión actual o null si es visitante
 */
function usuario_actual(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    static $cachedUser = false;
    if ($cachedUser !== false) {
        return $cachedUser;
    }

    $stmt = db()->prepare("
        SELECT id, nombre, correo, ocupacion, experiencia, rol, biografia, modalidad, ubicacion
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int) $_SESSION['usuario_id']]);
    $user = $stmt->fetch();

    $cachedUser = $user ?: null;
    return $cachedUser;
}

/**
 * Autentica al usuario por su correo y contraseña
 */
function iniciar_sesion_usuario(string $correo, string $password): array
{
    $correoLimpio = strtolower(trim($correo));
    if ($correoLimpio === '' || $password === '') {
        return ['exito' => false, 'error' => 'Por favor ingresa tu correo electrónico y contraseña.'];
    }

    $stmt = db()->prepare("SELECT * FROM usuarios WHERE LOWER(correo) = :correo LIMIT 1");
    $stmt->execute([':correo' => $correoLimpio]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        return ['exito' => false, 'error' => 'Credenciales incorrectas. Verifica tu correo o usa una cuenta demo.'];
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];

    return ['exito' => true, 'usuario' => $usuario];
}

/**
 * Registra un nuevo usuario en la base de datos y abre su sesión automáticamente
 */
function registrar_nuevo_usuario(array $datos): array
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $correo = strtolower(trim((string) ($datos['correo'] ?? '')));
    $ocupacion = trim((string) ($datos['ocupacion'] ?? 'Profesional / Estudiante / Autodidacta'));
    $experiencia = trim((string) ($datos['experiencia'] ?? 'Intermedio'));
    $ubicacion = trim((string) ($datos['ubicacion'] ?? 'Remoto / Online'));
    $rol = trim((string) ($datos['rol'] ?? 'ambos'));
    $password = (string) ($datos['password'] ?? '');

    if (strlen($nombre) < 4) {
        return ['exito' => false, 'error' => 'El nombre completo debe tener al menos 4 caracteres.'];
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return ['exito' => false, 'error' => 'Ingresa un correo electrónico válido (ej. nombre@correo.com).'];
    }
    if (strlen($password) < 6) {
        return ['exito' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'];
    }
    if (!in_array($rol, ['estudiante', 'mentor', 'ambos'], true)) {
        $rol = 'ambos';
    }
    if ($ocupacion === '') {
        $ocupacion = 'Entusiasta del aprendizaje';
    }
    if ($ubicacion === '') {
        $ubicacion = 'Remoto / Online';
    }

    // Verificar duplicados de correo
    $stmtCheck = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE LOWER(correo) = :correo");
    $stmtCheck->execute([':correo' => $correo]);
    if ((int) $stmtCheck->fetchColumn() > 0) {
        return ['exito' => false, 'error' => 'Ya existe una cuenta registrada con ese correo electrónico.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtInsert = db()->prepare("
        INSERT INTO usuarios (nombre, correo, ocupacion, experiencia, rol, biografia, modalidad, ubicacion, password_hash)
        VALUES (:nombre, :correo, :ocupacion, :experiencia, :rol, :bio, 'Ambas', :ubicacion, :hash)
    ");
    $stmtInsert->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':ocupacion' => $ocupacion,
        ':experiencia' => $experiencia,
        ':rol' => $rol,
        ':bio' => 'Integrante de la comunidad abierta de aprendizaje en MentorMatch.',
        ':ubicacion' => $ubicacion,
        ':hash' => $hash,
    ]);

    $nuevoId = (int) db()->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $nuevoId;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_rol'] = $rol;

    return ['exito' => true, 'usuario_id' => $nuevoId];
}

/**
 * Cierra la sesión activa de manera segura
 */
function cerrar_sesion_usuario(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }
    session_destroy();
}
