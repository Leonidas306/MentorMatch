<?php
declare(strict_types=1);

function dias_semana(): array
{
    return ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
}

function niveles_usuario(): array
{
    return ['Primaria', 'Secundaria', 'Preparatoria', 'Universidad', 'Otro'];
}

function etiqueta_nivel(string $nivel, string $nivelOtro = ''): string
{
    $nivelOtro = trim($nivelOtro);
    return ($nivel === 'Otro' && $nivelOtro !== '') ? $nivelOtro : $nivel;
}

function titular_publico(?string $ocupacion): string
{
    $ocupacion = trim((string) $ocupacion);
    return $ocupacion !== '' ? $ocupacion : 'Mentor';
}

function nombre_ciudad(string $ciudad): string
{
    $ciudad = trim((string) preg_replace('/\s*\/\s*(online|virtual|remoto)\s*$/iu', '', trim($ciudad)));
    return in_array(strtolower($ciudad), ['remoto', 'online', 'virtual'], true) ? '' : $ciudad;
}

function normalizar_ciudad(string $ciudad): string
{
    $ciudad = nombre_ciudad($ciudad);
    $ciudad = strtr($ciudad, [
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
    $ciudad = strtolower($ciudad);
    return trim((string) preg_replace('/\s+/', ' ', $ciudad));
}

function tiene_ciudad(?string $ubicacion): bool
{
    return normalizar_ciudad((string) $ubicacion) !== '';
}

function misma_ciudad(?string $a, ?string $b): bool
{
    return tiene_ciudad($a) && tiene_ciudad($b) && normalizar_ciudad((string) $a) === normalizar_ciudad((string) $b);
}

function niveles_habilidad(): array
{
    return ['Primaria', 'Secundaria', 'Preparatoria', 'Universidad', 'Todos los niveles'];
}

function definir_categorias(): array
{
    return [
        'Matemáticas' => [
            'nombre' => 'Matemáticas',
            'descripcion' => 'Álgebra, Cálculo, Geometría y Estadística',
            'icono' => 'math',
        ],
        'Física y Química' => [
            'nombre' => 'Física y Química',
            'descripcion' => 'Mecánica, Termodinámica y Química General',
            'icono' => 'atom',
        ],
        'Biología' => [
            'nombre' => 'Biología',
            'descripcion' => 'Célula, Genética y Ciencias de la Salud',
            'icono' => 'leaf',
        ],
        'Español y Humanidades' => [
            'nombre' => 'Español y Humanidades',
            'descripcion' => 'Redacción, Literatura e Historia',
            'icono' => 'book',
        ],
        'Inglés e Idiomas' => [
            'nombre' => 'Inglés e Idiomas',
            'descripcion' => 'Gramática, Conversación y Certificaciones',
            'icono' => 'lang',
        ],
        'Programación y Tecnología' => [
            'nombre' => 'Programación y Tecnología',
            'descripcion' => 'Programación, Bases de Datos y Desarrollo Web',
            'icono' => 'code',
        ],
    ];
}

function lista_areas(): array
{
    return array_keys(definir_categorias());
}

function etiqueta_tarifa(int $tarifa): string
{
    return $tarifa <= 0 ? 'Gratis' : '$' . number_format($tarifa) . ' / hr';
}

function formatear_experiencia(int $anios): string
{
    if ($anios <= 0) {
        return 'Primer año como mentor';
    }
    return $anios === 1 ? '1 año de experiencia' : $anios . ' años de experiencia';
}

function extraer_anios_experiencia(string $experiencia): int
{
    return preg_match('/^(\d+)/', trim($experiencia), $m) ? (int) $m[1] : 0;
}

function normalizar_telefono(string $telefono): ?string
{
    $telefono = trim($telefono);
    if ($telefono === '') {
        return '';
    }
    return preg_match('/^[0-9+\s()\-]{8,20}$/', $telefono) ? $telefono : null;
}

function es_hora_valida(string $hora): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora);
}

function nombre_dia_es(string $fecha): string
{
    $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    return $dias[(int) date('N', strtotime($fecha))];
}

function proxima_fecha_para_dia(string $dia): string
{
    $fecha = new DateTime('tomorrow');
    for ($i = 0; $i < 7; $i++) {
        if (nombre_dia_es($fecha->format('Y-m-d')) === $dia) {
            break;
        }
        $fecha->modify('+1 day');
    }
    return $fecha->format('Y-m-d');
}

function longitud_texto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
}

function recortar_texto(string $texto, int $max): string
{
    return function_exists('mb_substr') ? mb_substr($texto, 0, $max) : substr($texto, 0, $max);
}

function resultado_error(string $mensaje): array
{
    return ['exito' => false, 'error' => $mensaje];
}

const SQL_MENTOR_VISIBLE = "
    u.rol IN ('mentor', 'ambos')
    AND EXISTS (SELECT 1 FROM habilidades hx WHERE hx.usuario_id = u.id)
    AND EXISTS (SELECT 1 FROM disponibilidades dx WHERE dx.mentor_id = u.id AND dx.activo = 1)
";

const SQL_CALIFICACION_MENTOR = "
    (SELECT ROUND(AVG(rc.calificacion), 1) FROM resenas rc WHERE rc.mentor_id = u.id) AS calificacion_promedio,
    (SELECT COUNT(rc.calificacion) FROM resenas rc WHERE rc.mentor_id = u.id) AS total_calificaciones,
    (SELECT COUNT(*) FROM resenas rc WHERE rc.mentor_id = u.id) AS total_resenas
";

function obtener_categorias_con_conteo(): array
{
    $stmt = db()->query("
        SELECT h.area, COUNT(DISTINCT h.usuario_id) AS total_mentores
        FROM habilidades h
        INNER JOIN usuarios u ON u.id = h.usuario_id
        WHERE " . SQL_MENTOR_VISIBLE . "
        GROUP BY h.area
    ");
    $conteos = [];
    foreach ($stmt->fetchAll() as $row) {
        $conteos[$row['area']] = (int) $row['total_mentores'];
    }

    $resultado = [];
    foreach (definir_categorias() as $clave => $info) {
        $info['total_mentores'] = $conteos[$clave] ?? 0;
        $resultado[] = $info;
    }

    return $resultado;
}

function obtener_materias_disponibles(string $area = ''): array
{
    if ($area !== '') {
        $stmt = db()->prepare("SELECT DISTINCT materia, area FROM habilidades WHERE area = :area ORDER BY materia ASC");
        $stmt->execute([':area' => $area]);
        return $stmt->fetchAll();
    }

    $stmt = db()->query("SELECT DISTINCT materia, area FROM habilidades ORDER BY materia ASC");
    return $stmt->fetchAll();
}

function buscar_mentores(string $busqueda = '', string $materia = '', string $modalidad = '', string $area = '', string $nivel = '', string $ciudad = ''): array
{
    $sql = "
        SELECT DISTINCT
            u.id,
            u.nombre,
            u.correo,
            u.ocupacion,
            u.experiencia,
            u.rol,
            u.biografia,
            u.modalidad,
            u.ubicacion,
            u.tarifa_hora,
            " . SQL_CALIFICACION_MENTOR . "
        FROM usuarios u
        INNER JOIN habilidades h ON h.usuario_id = u.id
        WHERE " . SQL_MENTOR_VISIBLE . "
    ";

    $params = [];

    if ($area !== '') {
        $sql .= " AND h.area = :area";
        $params[':area'] = $area;
    }

    if ($materia !== '') {
        $sql .= " AND h.materia = :materia";
        $params[':materia'] = $materia;
    }

    if ($nivel !== '' && in_array($nivel, niveles_habilidad(), true)) {
        $sql .= " AND (h.nivel = :nivel OR h.nivel = 'Todos los niveles')";
        $params[':nivel'] = $nivel;
    }

    if ($modalidad !== '' && in_array($modalidad, ['Presencial', 'Virtual'], true)) {
        $sql .= " AND (u.modalidad = :modalidad OR u.modalidad = 'Ambas')";
        $params[':modalidad'] = $modalidad;
    }

    if ($busqueda !== '') {
        $sql .= " AND (
            u.nombre LIKE :q
            OR u.ocupacion LIKE :q
            OR h.materia LIKE :q
            OR h.area LIKE :q
            OR h.descripcion_tema LIKE :q
        )";
        $params[':q'] = '%' . trim($busqueda) . '%';
    }

    $sql .= " ORDER BY (calificacion_promedio IS NULL) ASC, calificacion_promedio DESC, total_calificaciones DESC, u.nombre ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $mentores = $stmt->fetchAll();

    foreach ($mentores as &$mentor) {
        $mentor['habilidades'] = obtener_habilidades_por_mentor((int) $mentor['id']);
        $mentor['disponibilidades'] = obtener_disponibilidades_por_mentor((int) $mentor['id']);
        $mentor['areas'] = array_values(array_unique(array_column($mentor['habilidades'], 'area')));
        $mentor['perfil_completo'] = !empty($mentor['habilidades'])
            && !empty($mentor['disponibilidades'])
            && trim((string) $mentor['biografia']) !== '';
        $mentor['en_tu_ciudad'] = $mentor['modalidad'] !== 'Virtual' && misma_ciudad($mentor['ubicacion'], $ciudad);
    }
    unset($mentor);

    if ($modalidad === 'Presencial' && tiene_ciudad($ciudad)) {
        $mentores = array_values(array_filter($mentores, fn(array $m): bool => $m['en_tu_ciudad']));
    }

    return $mentores;
}

function obtener_habilidades_por_mentor(int $mentorId): array
{
    $stmt = db()->prepare("
        SELECT id, materia, area, nivel, descripcion_tema
        FROM habilidades
        WHERE usuario_id = :uid
        ORDER BY materia ASC
    ");
    $stmt->execute([':uid' => $mentorId]);
    return $stmt->fetchAll();
}

function obtener_disponibilidades_por_mentor(int $mentorId): array
{
    $stmt = db()->prepare("
        SELECT id, dia_semana, hora_inicio, hora_fin, lugar_o_enlace, modalidad
        FROM disponibilidades
        WHERE mentor_id = :mid AND activo = 1
        ORDER BY
            CASE dia_semana
                WHEN 'Lunes' THEN 1 WHEN 'Martes' THEN 2 WHEN 'Miércoles' THEN 3
                WHEN 'Jueves' THEN 4 WHEN 'Viernes' THEN 5 WHEN 'Sábado' THEN 6 ELSE 7
            END,
            hora_inicio ASC
    ");
    $stmt->execute([':mid' => $mentorId]);
    return $stmt->fetchAll();
}

function obtener_mentor_por_id(int $mentorId): ?array
{
    $stmt = db()->prepare("
        SELECT u.id, u.nombre, u.ocupacion, u.experiencia, u.rol, u.biografia, u.modalidad, u.ubicacion, u.tarifa_hora,
               " . SQL_CALIFICACION_MENTOR . "
        FROM usuarios u
        WHERE u.id = :id AND u.rol IN ('mentor', 'ambos')
        LIMIT 1
    ");
    $stmt->execute([':id' => $mentorId]);
    $mentor = $stmt->fetch();

    if (!$mentor) {
        return null;
    }

    $mentor['habilidades'] = obtener_habilidades_por_mentor((int) $mentor['id']);
    $mentor['disponibilidades'] = obtener_disponibilidades_por_mentor((int) $mentor['id']);
    $mentor['areas'] = array_values(array_unique(array_column($mentor['habilidades'], 'area')));
    $mentor['perfil_completo'] = !empty($mentor['habilidades'])
        && !empty($mentor['disponibilidades'])
        && trim((string) $mentor['biografia']) !== '';
    return $mentor;
}

function tiene_conflicto_horario(int $usuarioId, string $fecha, string $horaInicio, string $horaFin, int $excluirSolicitudId = 0): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*) AS total
        FROM solicitudes
        WHERE (mentor_id = :uid OR estudiante_id = :uid)
          AND fecha_sesion = :fecha
          AND estado = 'aceptada'
          AND id <> :excluir
          AND hora_inicio < :fin
          AND hora_fin > :inicio
    ");
    $stmt->execute([
        ':uid' => $usuarioId,
        ':fecha' => $fecha,
        ':inicio' => $horaInicio,
        ':fin' => $horaFin,
        ':excluir' => $excluirSolicitudId,
    ]);
    return ((int) $stmt->fetchColumn()) > 0;
}

function crear_solicitud(int $estudianteId, int $mentorId, int $habilidadId, int $disponibilidadId, string $fecha, string $tema, string $mensaje): array
{
    if ($estudianteId === $mentorId) {
        return resultado_error('No puedes solicitarte una asesoría a ti mismo.');
    }

    $stmtRol = db()->prepare("SELECT rol FROM usuarios WHERE id = :id");
    $stmtRol->execute([':id' => $estudianteId]);
    $rolSolicitante = $stmtRol->fetchColumn();
    if ($rolSolicitante === false) {
        return resultado_error('Tu usuario no existe. Inicia sesión nuevamente.');
    }
    if ($rolSolicitante === 'mentor') {
        return resultado_error('Tu cuenta es de tipo Mentor. Para solicitar asesorías necesitas un perfil de Estudiante o de Ambos perfiles.');
    }

    $tema = trim($tema);
    $mensaje = recortar_texto(trim($mensaje), 500);
    if (longitud_texto($tema) < 5 || longitud_texto($tema) > 150) {
        return resultado_error('Describe el tema o duda específica (entre 5 y 150 caracteres).');
    }

    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$dt || $dt->format('Y-m-d') !== $fecha) {
        return resultado_error('La fecha propuesta no es válida.');
    }
    $hoy = date('Y-m-d');
    if ($fecha < $hoy) {
        return resultado_error('No se aceptan fechas anteriores a hoy (' . $hoy . ').');
    }

    $stmtHab = db()->prepare("SELECT materia FROM habilidades WHERE id = :id AND usuario_id = :mid");
    $stmtHab->execute([':id' => $habilidadId, ':mid' => $mentorId]);
    if ($stmtHab->fetchColumn() === false) {
        return resultado_error('La materia elegida no pertenece a este mentor.');
    }

    $stmtDisp = db()->prepare("SELECT * FROM disponibilidades WHERE id = :id AND mentor_id = :mid AND activo = 1");
    $stmtDisp->execute([':id' => $disponibilidadId, ':mid' => $mentorId]);
    $disp = $stmtDisp->fetch();
    if (!$disp) {
        return resultado_error('Selecciona uno de los bloques de disponibilidad vigentes del mentor.');
    }

    $diaFecha = nombre_dia_es($fecha);
    if ($diaFecha !== $disp['dia_semana']) {
        return resultado_error("La fecha elegida cae en {$diaFecha}, pero este bloque es de {$disp['dia_semana']}. Elige una fecha que coincida con el día del bloque.");
    }
    if ($fecha === $hoy && $disp['hora_inicio'] <= date('H:i')) {
        return resultado_error('Ese horario ya pasó el día de hoy. Elige otra fecha.');
    }

    if ($disp['modalidad'] === 'Presencial') {
        $stmtCiudades = db()->prepare("SELECT id, ubicacion FROM usuarios WHERE id IN (:est, :mid)");
        $stmtCiudades->execute([':est' => $estudianteId, ':mid' => $mentorId]);
        $ciudades = array_column($stmtCiudades->fetchAll(), 'ubicacion', 'id');
        $ciudadEst = nombre_ciudad((string) ($ciudades[$estudianteId] ?? ''));
        $ciudadMen = nombre_ciudad((string) ($ciudades[$mentorId] ?? ''));
        if ($ciudadEst === '') {
            return resultado_error('Para pedir una clase presencial primero registra tu ciudad (la puedes guardar en esta misma página) o elige un horario virtual.');
        }
        if (!misma_ciudad($ciudadEst, $ciudadMen)) {
            return resultado_error("Este bloque es presencial en {$ciudadMen} y tu ciudad es {$ciudadEst}. Las clases presenciales solo se agendan con mentores de tu ciudad; elige un bloque virtual.");
        }
    }

    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        if (tiene_conflicto_horario($mentorId, $fecha, $disp['hora_inicio'], $disp['hora_fin'])) {
            $pdo->exec('ROLLBACK');
            return resultado_error("El mentor ya tiene una sesión aceptada el {$fecha} que choca con ese horario ({$disp['hora_inicio']} - {$disp['hora_fin']}). Elige otra fecha u otro bloque.");
        }
        if (tiene_conflicto_horario($estudianteId, $fecha, $disp['hora_inicio'], $disp['hora_fin'])) {
            $pdo->exec('ROLLBACK');
            return resultado_error("Ya tienes una sesión aceptada el {$fecha} que choca con ese horario ({$disp['hora_inicio']} - {$disp['hora_fin']}). Elige otro horario.");
        }

        $stmtDup = $pdo->prepare("
            SELECT COUNT(*) FROM solicitudes
            WHERE estudiante_id = :est AND mentor_id = :mid AND fecha_sesion = :fecha
              AND estado IN ('pendiente', 'aceptada')
              AND hora_inicio < :fin AND hora_fin > :inicio
        ");
        $stmtDup->execute([
            ':est' => $estudianteId, ':mid' => $mentorId, ':fecha' => $fecha,
            ':inicio' => $disp['hora_inicio'], ':fin' => $disp['hora_fin'],
        ]);
        if ((int) $stmtDup->fetchColumn() > 0) {
            $pdo->exec('ROLLBACK');
            return resultado_error('Ya tienes una solicitud activa con este mentor en ese horario.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO solicitudes
                (estudiante_id, mentor_id, habilidad_id, disponibilidad_id, fecha_sesion, hora_inicio, hora_fin, modalidad, tema_especifico, mensaje_estudiante, estado)
            VALUES
                (:est, :mid, :hab, :disp, :fecha, :inicio, :fin, :modalidad, :tema, :mensaje, 'pendiente')
        ");
        $stmt->execute([
            ':est' => $estudianteId, ':mid' => $mentorId, ':hab' => $habilidadId, ':disp' => $disponibilidadId,
            ':fecha' => $fecha, ':inicio' => $disp['hora_inicio'], ':fin' => $disp['hora_fin'],
            ':modalidad' => $disp['modalidad'], ':tema' => $tema, ':mensaje' => $mensaje,
        ]);
        $id = (int) $pdo->lastInsertId();
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        return resultado_error('No se pudo guardar la solicitud. Intenta de nuevo.');
    }

    return ['exito' => true, 'id' => $id];
}

function obtener_solicitudes_agenda(int $usuarioId): array
{
    $stmt = db()->prepare("
        SELECT
            s.id,
            s.estudiante_id,
            est.nombre AS estudiante_nombre,
            CASE WHEN est.nivel_educativo = 'Otro' AND est.nivel_otro <> '' THEN est.nivel_otro ELSE est.nivel_educativo END AS estudiante_nivel,
            est.telefono AS estudiante_telefono,
            s.mentor_id,
            men.nombre AS mentor_nombre,
            men.ocupacion AS mentor_ocupacion,
            men.telefono AS mentor_telefono,
            h.materia,
            s.fecha_sesion,
            s.hora_inicio,
            s.hora_fin,
            s.modalidad,
            s.tema_especifico,
            s.mensaje_estudiante,
            s.estado,
            CASE WHEN ses.estado = 'completada' THEN 'completada' ELSE s.estado END AS estado_efectivo,
            s.creado_en,
            ses.estado AS estado_sesion,
            ses.lugar_o_enlace,
            ses.notas_cierre,
            rs.id AS resena_id,
            rs.calificacion AS resena_calificacion,
            rs.comentario AS resena_comentario
        FROM solicitudes s
        INNER JOIN usuarios est ON est.id = s.estudiante_id
        INNER JOIN usuarios men ON men.id = s.mentor_id
        INNER JOIN habilidades h ON h.id = s.habilidad_id
        LEFT JOIN sesiones ses ON ses.solicitud_id = s.id
        LEFT JOIN resenas rs ON rs.sesion_id = ses.id
        WHERE s.estudiante_id = :uid OR s.mentor_id = :uid
        ORDER BY s.fecha_sesion DESC, s.hora_inicio ASC
    ");
    $stmt->execute([':uid' => $usuarioId]);
    return $stmt->fetchAll();
}

function responder_solicitud(int $solicitudId, int $mentorId, string $accion): array
{
    if (!in_array($accion, ['aceptar', 'rechazar'], true)) {
        return resultado_error('Acción no válida.');
    }

    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, h.materia, d.lugar_o_enlace AS lugar
            FROM solicitudes s
            INNER JOIN habilidades h ON h.id = s.habilidad_id
            LEFT JOIN disponibilidades d ON d.id = s.disponibilidad_id
            WHERE s.id = :id AND s.mentor_id = :mid
        ");
        $stmt->execute([':id' => $solicitudId, ':mid' => $mentorId]);
        $sol = $stmt->fetch();

        if (!$sol) {
            $pdo->exec('ROLLBACK');
            return resultado_error('Solicitud no encontrada.');
        }
        if ($sol['estado'] !== 'pendiente') {
            $pdo->exec('ROLLBACK');
            return resultado_error('Esta solicitud ya fue respondida (estado: ' . $sol['estado'] . ').');
        }

        if ($accion === 'rechazar') {
            $pdo->prepare("UPDATE solicitudes SET estado = 'rechazada' WHERE id = :id")->execute([':id' => $solicitudId]);
            $pdo->exec('COMMIT');
            return ['exito' => true, 'mensaje' => 'Solicitud rechazada. El horario quedó libre para otras solicitudes.'];
        }

        if ($sol['fecha_sesion'] < date('Y-m-d')) {
            $pdo->exec('ROLLBACK');
            return resultado_error('La fecha de esta solicitud ya pasó; no se puede aceptar.');
        }
        if (tiene_conflicto_horario($mentorId, $sol['fecha_sesion'], $sol['hora_inicio'], $sol['hora_fin'], $solicitudId)) {
            $pdo->exec('ROLLBACK');
            return resultado_error('Ya tienes otra sesión aceptada que se traslapa con ese horario. No se pueden aceptar dos sesiones al mismo tiempo.');
        }
        if (tiene_conflicto_horario((int) $sol['estudiante_id'], $sol['fecha_sesion'], $sol['hora_inicio'], $sol['hora_fin'], $solicitudId)) {
            $pdo->exec('ROLLBACK');
            return resultado_error('El estudiante ya tiene otra sesión aceptada en ese horario, así que no se puede aceptar. Puedes rechazar esta solicitud.');
        }

        $pdo->prepare("UPDATE solicitudes SET estado = 'aceptada' WHERE id = :id")->execute([':id' => $solicitudId]);
        $pdo->prepare("
            INSERT INTO sesiones (solicitud_id, mentor_id, estudiante_id, materia, fecha_sesion, hora_inicio, hora_fin, lugar_o_enlace, estado)
            VALUES (:sid, :mid, :est, :materia, :fecha, :inicio, :fin, :lugar, 'programada')
        ")->execute([
            ':sid' => $solicitudId, ':mid' => $mentorId, ':est' => $sol['estudiante_id'],
            ':materia' => $sol['materia'], ':fecha' => $sol['fecha_sesion'],
            ':inicio' => $sol['hora_inicio'], ':fin' => $sol['hora_fin'],
            ':lugar' => $sol['lugar'] ?: 'Por confirmar con el mentor',
        ]);

        $auto = $pdo->prepare("
            UPDATE solicitudes SET estado = 'rechazada'
            WHERE (mentor_id IN (:mid, :est) OR estudiante_id IN (:mid, :est))
              AND fecha_sesion = :fecha AND estado = 'pendiente' AND id <> :id
              AND hora_inicio < :fin AND hora_fin > :inicio
        ");
        $auto->execute([
            ':mid' => $mentorId, ':est' => (int) $sol['estudiante_id'], ':fecha' => $sol['fecha_sesion'], ':id' => $solicitudId,
            ':inicio' => $sol['hora_inicio'], ':fin' => $sol['hora_fin'],
        ]);
        $rechazadas = $auto->rowCount();

        $pdo->exec('COMMIT');
        $extra = $rechazadas > 0 ? " Se rechazaron automáticamente {$rechazadas} solicitud(es) pendiente(s) que chocaban con ese horario." : '';
        return ['exito' => true, 'mensaje' => 'Solicitud aceptada. La sesión quedó programada.' . $extra];
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        return resultado_error('No se pudo actualizar la solicitud. Intenta de nuevo.');
    }
}

function actualizar_ciudad_usuario(int $usuarioId, string $ciudad): array
{
    $ciudad = trim($ciudad);
    if (longitud_texto($ciudad) < 2 || longitud_texto($ciudad) > 80) {
        return resultado_error('Escribe tu ciudad (entre 2 y 80 caracteres).');
    }
    if (!tiene_ciudad($ciudad)) {
        return resultado_error('Escribe el nombre de tu ciudad (no "Online" o "Remoto").');
    }
    db()->prepare("UPDATE usuarios SET ubicacion = :ubi WHERE id = :id")
        ->execute([':ubi' => recortar_texto($ciudad, 80), ':id' => $usuarioId]);
    return ['exito' => true, 'mensaje' => 'Ciudad guardada: ' . recortar_texto($ciudad, 80) . '.'];
}

function cancelar_solicitud(int $solicitudId, int $usuarioId): array
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE id = :id AND (estudiante_id = :uid OR mentor_id = :uid)");
        $stmt->execute([':id' => $solicitudId, ':uid' => $usuarioId]);
        $sol = $stmt->fetch();

        if (!$sol) {
            $pdo->exec('ROLLBACK');
            return resultado_error('Solicitud no encontrada.');
        }

        if ($sol['estado'] === 'pendiente') {
            if ((int) $sol['estudiante_id'] !== $usuarioId) {
                $pdo->exec('ROLLBACK');
                return resultado_error('Como mentor, usa "Rechazar" para responder una solicitud pendiente.');
            }
        } elseif ($sol['estado'] === 'aceptada') {
            if ($sol['fecha_sesion'] < date('Y-m-d')) {
                $pdo->exec('ROLLBACK');
                return resultado_error('La sesión ya pasó. El mentor puede marcarla como completada.');
            }
        } else {
            $pdo->exec('ROLLBACK');
            return resultado_error('No se puede cancelar una solicitud en estado "' . $sol['estado'] . '".');
        }

        $pdo->prepare("UPDATE solicitudes SET estado = 'cancelada' WHERE id = :id")->execute([':id' => $solicitudId]);
        $pdo->prepare("UPDATE sesiones SET estado = 'cancelada', actualizado_en = datetime('now', 'localtime') WHERE solicitud_id = :id")
            ->execute([':id' => $solicitudId]);
        $pdo->exec('COMMIT');
        return ['exito' => true, 'mensaje' => 'Solicitud cancelada. El horario quedó libre.'];
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        return resultado_error('No se pudo cancelar. Intenta de nuevo.');
    }
}

function completar_sesion(int $solicitudId, int $mentorId, string $notas): array
{
    $notas = recortar_texto(trim($notas), 500);

    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $stmt = $pdo->prepare("
            SELECT s.fecha_sesion, s.hora_inicio, s.estado, ses.estado AS estado_sesion
            FROM solicitudes s
            LEFT JOIN sesiones ses ON ses.solicitud_id = s.id
            WHERE s.id = :id AND s.mentor_id = :mid
        ");
        $stmt->execute([':id' => $solicitudId, ':mid' => $mentorId]);
        $sol = $stmt->fetch();

        if (!$sol) {
            $pdo->exec('ROLLBACK');
            return resultado_error('Sesión no encontrada.');
        }
        if ($sol['estado'] !== 'aceptada' || $sol['estado_sesion'] !== 'programada') {
            $pdo->exec('ROLLBACK');
            return resultado_error('Solo se pueden completar sesiones aceptadas y programadas.');
        }
        $yaInicio = $sol['fecha_sesion'] < date('Y-m-d')
            || ($sol['fecha_sesion'] === date('Y-m-d') && $sol['hora_inicio'] <= date('H:i'));
        if (!$yaInicio) {
            $pdo->exec('ROLLBACK');
            return resultado_error('La sesión aún no ha iniciado; no se puede marcar como completada.');
        }

        $pdo->prepare("
            UPDATE sesiones
            SET estado = 'completada', notas_cierre = :notas, actualizado_en = datetime('now', 'localtime')
            WHERE solicitud_id = :id
        ")->execute([':notas' => $notas, ':id' => $solicitudId]);
        $pdo->exec('COMMIT');
        return ['exito' => true, 'mensaje' => 'Sesión marcada como completada y enviada al historial.'];
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        return resultado_error('No se pudo completar la sesión. Intenta de nuevo.');
    }
}

function nombre_publico(string $nombreCompleto): string
{
    $partes = preg_split('/\s+/', trim($nombreCompleto), -1, PREG_SPLIT_NO_EMPTY);
    if (count($partes) <= 1) {
        return $partes[0] ?? 'Estudiante';
    }
    return $partes[0] . ' ' . recortar_texto((string) end($partes), 1) . '.';
}

function crear_resena(int $estudianteId, int $solicitudId, ?int $calificacion, string $comentario): array
{
    $comentario = trim($comentario);

    if ($calificacion !== null && ($calificacion < 1 || $calificacion > 5)) {
        return resultado_error('La calificación debe ser de 1 a 5 estrellas.');
    }
    if (longitud_texto($comentario) > 500) {
        return resultado_error('El comentario no puede pasar de 500 caracteres.');
    }
    if ($calificacion === null && $comentario === '') {
        return resultado_error('Elige una calificación o escribe un comentario. La reseña es opcional, así que también puedes omitirla.');
    }

    $stmt = db()->prepare("
        SELECT ses.id, ses.mentor_id, ses.estado
        FROM sesiones ses
        INNER JOIN solicitudes s ON s.id = ses.solicitud_id
        WHERE ses.solicitud_id = :sid AND s.estudiante_id = :uid
    ");
    $stmt->execute([':sid' => $solicitudId, ':uid' => $estudianteId]);
    $sesion = $stmt->fetch();

    if (!$sesion) {
        return resultado_error('Sesión no encontrada. Solo puedes reseñar tus propias sesiones.');
    }
    if ($sesion['estado'] !== 'completada') {
        return resultado_error('Solo puedes reseñar sesiones que el mentor ya marcó como completadas.');
    }

    $stmtYa = db()->prepare("SELECT COUNT(*) FROM resenas WHERE sesion_id = :id");
    $stmtYa->execute([':id' => $sesion['id']]);
    if ((int) $stmtYa->fetchColumn() > 0) {
        return resultado_error('Ya dejaste una reseña para esta sesión.');
    }

    try {
        db()->prepare("
            INSERT INTO resenas (sesion_id, mentor_id, estudiante_id, calificacion, comentario)
            VALUES (:sesion, :mentor, :est, :cal, :comentario)
        ")->execute([
            ':sesion' => $sesion['id'], ':mentor' => $sesion['mentor_id'], ':est' => $estudianteId,
            ':cal' => $calificacion, ':comentario' => $comentario,
        ]);
    } catch (PDOException $e) {
        return resultado_error('Ya dejaste una reseña para esta sesión.');
    }

    return ['exito' => true, 'mensaje' => '¡Gracias! Tu reseña ya aparece en el perfil del mentor.'];
}

function obtener_resenas_mentor(int $mentorId, int $limite = 30): array
{
    $stmt = db()->prepare("
        SELECT r.id, r.calificacion, r.comentario, r.creado_en,
               est.nombre AS estudiante_nombre,
               ses.materia, ses.fecha_sesion
        FROM resenas r
        INNER JOIN usuarios est ON est.id = r.estudiante_id
        INNER JOIN sesiones ses ON ses.id = r.sesion_id
        WHERE r.mentor_id = :mid
        ORDER BY r.creado_en DESC, r.id DESC
        LIMIT " . max(1, $limite));
    $stmt->execute([':mid' => $mentorId]);
    $resenas = $stmt->fetchAll();
    foreach ($resenas as &$resena) {
        $resena['estudiante_publico'] = nombre_publico((string) $resena['estudiante_nombre']);
    }
    unset($resena);
    return $resenas;
}

function obtener_resumen_resenas(int $mentorId): array
{
    $stmt = db()->prepare("SELECT calificacion, COUNT(*) AS total FROM resenas WHERE mentor_id = :mid AND calificacion IS NOT NULL GROUP BY calificacion");
    $stmt->execute([':mid' => $mentorId]);
    $distribucion = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $suma = 0;
    $conteo = 0;
    foreach ($stmt->fetchAll() as $fila) {
        $distribucion[(int) $fila['calificacion']] = (int) $fila['total'];
        $suma += (int) $fila['calificacion'] * (int) $fila['total'];
        $conteo += (int) $fila['total'];
    }

    $stmtTotal = db()->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN comentario <> '' THEN 1 ELSE 0 END) AS comentarios FROM resenas WHERE mentor_id = :mid");
    $stmtTotal->execute([':mid' => $mentorId]);
    $fila = $stmtTotal->fetch();

    return [
        'promedio' => $conteo > 0 ? round($suma / $conteo, 1) : null,
        'total_calificaciones' => $conteo,
        'total_resenas' => (int) ($fila['total'] ?? 0),
        'total_comentarios' => (int) ($fila['comentarios'] ?? 0),
        'distribucion' => $distribucion,
    ];
}

function usuario_puede_ser_mentor(int $usuarioId): bool
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id AND rol IN ('mentor', 'ambos')");
    $stmt->execute([':id' => $usuarioId]);
    return (int) $stmt->fetchColumn() > 0;
}

function actualizar_perfil_mentor(int $usuarioId, array $datos): array
{
    $titular = trim((string) ($datos['ocupacion'] ?? ''));
    $biografia = trim((string) ($datos['biografia'] ?? ''));
    $anios = (int) ($datos['anios_experiencia'] ?? 0);
    $modalidad = (string) ($datos['modalidad'] ?? 'Ambas');
    $ubicacion = trim((string) ($datos['ubicacion'] ?? ''));
    $telefono = normalizar_telefono((string) ($datos['telefono'] ?? ''));
    $tarifa = (int) ($datos['tarifa_hora'] ?? 0);

    if (longitud_texto($titular) < 3 || longitud_texto($titular) > 120) {
        return resultado_error('El titular del perfil debe tener entre 3 y 120 caracteres (ej. "Ing. en Sistemas & Tutor de Matemáticas").');
    }
    if (longitud_texto($biografia) > 600) {
        return resultado_error('La biografía no puede pasar de 600 caracteres.');
    }
    if ($anios < 0 || $anios > 60) {
        return resultado_error('Los años de experiencia deben estar entre 0 y 60.');
    }
    if (!in_array($modalidad, ['Presencial', 'Virtual', 'Ambas'], true)) {
        return resultado_error('Modalidad no válida.');
    }
    if ($telefono === null) {
        return resultado_error('El teléfono solo puede contener números, espacios, +, paréntesis y guiones (8 a 20 caracteres).');
    }
    if ($tarifa < 0 || $tarifa > 5000) {
        return resultado_error('La tarifa por hora debe estar entre 0 (gratis) y 5000.');
    }
    if ($modalidad === 'Virtual' || $ubicacion === '') {
        $ubicacion = 'Remoto / Online';
    }

    $stmt = db()->prepare("
        UPDATE usuarios
        SET ocupacion = :ocupacion, biografia = :bio, experiencia = :exp, modalidad = :mod,
            ubicacion = :ubi, telefono = :tel, tarifa_hora = :tarifa
        WHERE id = :id AND rol IN ('mentor', 'ambos')
    ");
    $stmt->execute([
        ':ocupacion' => $titular, ':bio' => $biografia, ':exp' => formatear_experiencia($anios),
        ':mod' => $modalidad, ':ubi' => recortar_texto($ubicacion, 80), ':tel' => $telefono,
        ':tarifa' => $tarifa, ':id' => $usuarioId,
    ]);

    return ['exito' => true, 'mensaje' => 'Perfil actualizado.'];
}

function crear_habilidad(int $usuarioId, string $materia, string $area, string $nivel, string $descripcion): array
{
    $materia = trim($materia);
    $descripcion = trim($descripcion);

    if (!usuario_puede_ser_mentor($usuarioId)) {
        return resultado_error('Solo los mentores pueden publicar materias.');
    }
    if (longitud_texto($materia) < 3 || longitud_texto($materia) > 80) {
        return resultado_error('El nombre de la materia debe tener entre 3 y 80 caracteres.');
    }
    if (!in_array($area, lista_areas(), true)) {
        return resultado_error('Elige una categoría válida.');
    }
    if (!in_array($nivel, niveles_habilidad(), true)) {
        return resultado_error('Elige un nivel educativo válido.');
    }
    if (longitud_texto($descripcion) < 10 || longitud_texto($descripcion) > 300) {
        return resultado_error('Describe los temas que cubres (entre 10 y 300 caracteres).');
    }

    $stmtDup = db()->prepare("SELECT COUNT(*) FROM habilidades WHERE usuario_id = :uid AND LOWER(materia) = LOWER(:materia)");
    $stmtDup->execute([':uid' => $usuarioId, ':materia' => $materia]);
    if ((int) $stmtDup->fetchColumn() > 0) {
        return resultado_error('Ya publicaste una materia con ese nombre.');
    }

    db()->prepare("
        INSERT INTO habilidades (usuario_id, materia, area, nivel, descripcion_tema)
        VALUES (:uid, :materia, :area, :nivel, :desc)
    ")->execute([':uid' => $usuarioId, ':materia' => $materia, ':area' => $area, ':nivel' => $nivel, ':desc' => $descripcion]);

    return ['exito' => true, 'mensaje' => 'Materia publicada.'];
}

function eliminar_habilidad(int $usuarioId, int $habilidadId): array
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM habilidades WHERE id = :id AND usuario_id = :uid");
    $stmt->execute([':id' => $habilidadId, ':uid' => $usuarioId]);
    if ((int) $stmt->fetchColumn() === 0) {
        return resultado_error('Materia no encontrada.');
    }

    $stmtSol = db()->prepare("SELECT COUNT(*) FROM solicitudes WHERE habilidad_id = :id");
    $stmtSol->execute([':id' => $habilidadId]);
    if ((int) $stmtSol->fetchColumn() > 0) {
        return resultado_error('Esta materia ya tiene solicitudes o sesiones en el historial, por eso no se puede eliminar.');
    }

    db()->prepare("DELETE FROM habilidades WHERE id = :id AND usuario_id = :uid")->execute([':id' => $habilidadId, ':uid' => $usuarioId]);
    return ['exito' => true, 'mensaje' => 'Materia eliminada.'];
}

function crear_disponibilidad(int $mentorId, string $dia, string $inicio, string $fin, string $modalidad, string $lugar): array
{
    $lugar = trim($lugar);

    if (!usuario_puede_ser_mentor($mentorId)) {
        return resultado_error('Solo los mentores pueden publicar horarios.');
    }
    if (!in_array($dia, dias_semana(), true)) {
        return resultado_error('Elige un día de la semana válido.');
    }
    if (!es_hora_valida($inicio) || !es_hora_valida($fin)) {
        return resultado_error('Las horas deben tener el formato HH:MM.');
    }
    if ($fin <= $inicio) {
        return resultado_error('La hora de fin debe ser posterior a la hora de inicio.');
    }
    if (!in_array($modalidad, ['Presencial', 'Virtual'], true)) {
        return resultado_error('Elige una modalidad válida.');
    }
    if ($modalidad === 'Presencial' && longitud_texto($lugar) < 3) {
        return resultado_error('Indica el lugar donde será la sesión presencial.');
    }
    if ($lugar === '') {
        $lugar = 'Videollamada (enlace al confirmar)';
    }

    $stmtTraslape = db()->prepare("
        SELECT COUNT(*) FROM disponibilidades
        WHERE mentor_id = :mid AND activo = 1 AND dia_semana = :dia
          AND hora_inicio < :fin AND hora_fin > :inicio
    ");
    $stmtTraslape->execute([':mid' => $mentorId, ':dia' => $dia, ':inicio' => $inicio, ':fin' => $fin]);
    if ((int) $stmtTraslape->fetchColumn() > 0) {
        return resultado_error('Ese bloque se traslapa con otro horario que ya publicaste ese día.');
    }

    db()->prepare("
        INSERT INTO disponibilidades (mentor_id, dia_semana, hora_inicio, hora_fin, lugar_o_enlace, modalidad, activo)
        VALUES (:mid, :dia, :inicio, :fin, :lugar, :modalidad, 1)
    ")->execute([
        ':mid' => $mentorId, ':dia' => $dia, ':inicio' => $inicio, ':fin' => $fin,
        ':lugar' => recortar_texto($lugar, 120), ':modalidad' => $modalidad,
    ]);

    return ['exito' => true, 'mensaje' => 'Horario publicado.'];
}

function eliminar_disponibilidad(int $mentorId, int $disponibilidadId): array
{
    $stmt = db()->prepare("UPDATE disponibilidades SET activo = 0 WHERE id = :id AND mentor_id = :mid AND activo = 1");
    $stmt->execute([':id' => $disponibilidadId, ':mid' => $mentorId]);
    if ($stmt->rowCount() === 0) {
        return resultado_error('Horario no encontrado.');
    }
    return ['exito' => true, 'mensaje' => 'Horario eliminado. Las solicitudes ya existentes se conservan.'];
}

function obtener_estadisticas_sistema(): array
{
    $pdo = db();
    $totalMentores = (int) $pdo->query("SELECT COUNT(*) FROM usuarios u WHERE " . SQL_MENTOR_VISIBLE)->fetchColumn();
    $totalMaterias = (int) $pdo->query("SELECT COUNT(DISTINCT materia) FROM habilidades")->fetchColumn();
    $totalHorarios = (int) $pdo->query("SELECT COUNT(*) FROM disponibilidades WHERE activo = 1")->fetchColumn();
    $totalSolicitudes = (int) $pdo->query("SELECT COUNT(*) FROM solicitudes")->fetchColumn();
    $totalCompletadas = (int) $pdo->query("SELECT COUNT(*) FROM sesiones WHERE estado = 'completada'")->fetchColumn();

    return [
        'mentores' => $totalMentores,
        'materias' => $totalMaterias,
        'horarios' => $totalHorarios,
        'solicitudes' => $totalSolicitudes,
        'completadas' => $totalCompletadas,
    ];
}