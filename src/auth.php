<?php
declare(strict_types=1);


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
        SELECT id, nombre, correo, ocupacion, experiencia, nivel_educativo, nivel_otro, rol, biografia, modalidad, ubicacion, telefono, tarifa_hora
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int) $_SESSION['usuario_id']]);
    $user = $stmt->fetch();

    $cachedUser = $user ?: null;
    return $cachedUser;
}


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
        return ['exito' => false, 'error' => 'Credenciales incorrectas. Verifica tu correo y tu contraseña.'];
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];

    return ['exito' => true, 'usuario' => $usuario];
}

/**
 * Registra un nuevo usuario en la base de datos y abre su sesión automáticamente.
 * La biografía y las materias/horarios se completan después en "Mi Panel".
 */
function registrar_nuevo_usuario(array $datos): array
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $correo = strtolower(trim((string) ($datos['correo'] ?? '')));
    $rol = trim((string) ($datos['rol'] ?? 'estudiante'));
    $password = (string) ($datos['password'] ?? '');
    $nivel = trim((string) ($datos['nivel_educativo'] ?? ''));
    $modalidad = trim((string) ($datos['modalidad'] ?? 'Ambas'));
    $ubicacion = trim((string) ($datos['ubicacion'] ?? ''));
    $telefono = normalizar_telefono((string) ($datos['telefono'] ?? ''));

    if (longitud_texto($nombre) < 4) {
        return ['exito' => false, 'error' => 'El nombre completo debe tener al menos 4 caracteres.'];
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return ['exito' => false, 'error' => 'Ingresa un correo electrónico válido (ej. nombre@correo.com).'];
    }
    if (strlen($password) < 6) {
        return ['exito' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'];
    }
    if (!in_array($rol, ['estudiante', 'mentor', 'ambos'], true)) {
        return ['exito' => false, 'error' => 'Elige cómo vas a participar en la plataforma.'];
    }
    if (!in_array($nivel, niveles_usuario(), true)) {
        return ['exito' => false, 'error' => 'Selecciona tu nivel educativo.'];
    }
    // al elegir "Otro" debe escribir cuál es (texto libre).
    $nivelOtro = '';
    if ($nivel === 'Otro') {
        $nivelOtro = trim((string) ($datos['nivel_otro'] ?? ''));
        if (longitud_texto($nivelOtro) < 2 || longitud_texto($nivelOtro) > 40) {
            return ['exito' => false, 'error' => 'Escribe cuál es tu grado o nivel 
            educativo (entre 2 y 40 caracteres).'];
        }
    }
    if (!in_array($modalidad, ['Presencial', 'Virtual', 'Ambas'], true)) {
        $modalidad = 'Ambas';
    }
    if ($telefono === null) {
        return ['exito' => false, 'error' => 'El teléfono solo puede contener números, 
        espacios, +, paréntesis y guiones (8 a 20 caracteres).'];
    }
    if ($modalidad === 'Virtual' || $ubicacion === '') {
        $ubicacion = 'Remoto / Online';
    }

    if ($rol === 'estudiante') {
        $experiencia = 'Estudiante';
        $titular = 'Estudiante de ' . etiqueta_nivel($nivel, $nivelOtro);
    } else {
        $anios = (int) ($datos['anios_experiencia'] ?? 0);
        if ($anios < 0 || $anios > 60) {
            return ['exito' => false, 'error' => 'Los años de experiencia dando clases 
            deben estar entre 0 y 60.'];
        }
        $experiencia = formatear_experiencia($anios);
        $titular = ''; // Mentor escribe su propio titulo en "Mi Panel"
    }

    // Verificar duplicados de correo
    $stmtCheck = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE LOWER(correo) = :correo");
    $stmtCheck->execute([':correo' => $correo]);
    if ((int) $stmtCheck->fetchColumn() > 0) {
        return ['exito' => false, 'error' => 'Ya existe una cuenta registrada con 
        ese correo electrónico.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtInsert = db()->prepare("
        INSERT INTO usuarios (nombre, correo, ocupacion, experiencia, nivel_educativo, 
        'nivel_otro, rol, biografia, modalidad, ubicacion, telefono, password_hash)
        VALUES (:nombre, :correo, :ocupacion, :experiencia, :nivel, :nivel_otro, :rol, 
        '', :modalidad, :ubicacion, :telefono, :hash)
    ");
    $stmtInsert->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':ocupacion' => $titular,
        ':experiencia' => $experiencia,
        ':nivel' => $nivel,
        ':nivel_otro' => $nivelOtro,
        ':rol' => $rol,
        ':modalidad' => $modalidad,
        ':ubicacion' => recortar_texto($ubicacion, 80),
        ':telefono' => $telefono,
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
