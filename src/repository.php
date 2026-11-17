<?php
declare(strict_types=1);

/**
 * Catálogo oficial de las 6 categorías principales de MentorMatch con su metadato visual
 * y conteo en vivo de mentores que imparten materias en cada área.
 */
function obtener_categorias_con_conteo(): array
{
    $definiciones = [
        'Ciencia' => [
            'nombre' => 'Ciencia',
            'descripcion' => 'Física, Estadística y Método Científico',
            'icono' => 'atom',
        ],
        'Matemáticas' => [
            'nombre' => 'Matemáticas',
            'descripcion' => 'Cálculo, Álgebra Lineal y Geometría',
            'icono' => 'math',
        ],
        'Tecnología y Código' => [
            'nombre' => 'Tecnología y Código',
            'descripcion' => 'Programación, Desarrollo Web y SQL',
            'icono' => 'code',
        ],
        'Diseño y Creatividad' => [
            'nombre' => 'Diseño y Creatividad',
            'descripcion' => 'UX/UI, Figma y Prototipado',
            'icono' => 'design',
        ],
        'Negocios y Finanzas' => [
            'nombre' => 'Negocios y Finanzas',
            'descripcion' => 'Finanzas Personales y Excel Avanzado',
            'icono' => 'finance',
        ],
        'Idiomas' => [
            'nombre' => 'Idiomas',
            'descripcion' => 'Inglés Conversacional y Entrevistas',
            'icono' => 'lang',
        ],
    ];

    $stmt = db()->query("
        SELECT h.area, COUNT(DISTINCT h.usuario_id) AS total_mentores
        FROM habilidades h
        INNER JOIN usuarios u ON u.id = h.usuario_id
        WHERE u.rol IN ('mentor', 'ambos')
        GROUP BY h.area
    ");
    $conteos = [];
    foreach ($stmt->fetchAll() as $row) {
        $conteos[$row['area']] = (int) $row['total_mentores'];
    }

    $resultado = [];
    foreach ($definiciones as $clave => $info) {
        $info['total_mentores'] = $conteos[$clave] ?? 0;
        $resultado[] = $info;
    }

    return $resultado;
}

/**
 * Obtiene la lista de todas las materias/habilidades únicas registradas para poblar el selector.
 */
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

/**
 * Busca mentores filtrando por área/categoría, materia exacta, texto libre y modalidad.
 * Calcula automáticamente si el mentor tiene 'perfil_completo' (al menos 1 materia, 1 horario y biografía).
 */
function buscar_mentores(string $busqueda = '', string $materia = '', string $modalidad = '', string $area = ''): array
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
            u.calificacion_promedio
        FROM usuarios u
        INNER JOIN habilidades h ON h.usuario_id = u.id
        WHERE u.rol IN ('mentor', 'ambos')
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

    $sql .= " ORDER BY u.calificacion_promedio DESC, u.nombre ASC";

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
    }

    return $mentores;
}

/**
 * Obtiene las habilidades/materias de un mentor específico.
 */
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

/**
 * Obtiene los bloques de horario disponibles de un mentor específico.
 */
function obtener_disponibilidades_por_mentor(int $mentorId): array
{
    $stmt = db()->prepare("
        SELECT id, dia_semana, hora_inicio, hora_fin, lugar_o_enlace, modalidad
        FROM disponibilidades
        WHERE mentor_id = :mid AND activo = 1
        ORDER BY id ASC
    ");
    $stmt->execute([':mid' => $mentorId]);
    return $stmt->fetchAll();
}

/**
 * Obtiene el perfil completo de un mentor por su ID.
 */
function obtener_mentor_por_id(int $mentorId): ?array
{
    $stmt = db()->prepare("
        SELECT id, nombre, correo, ocupacion, experiencia, rol, biografia, modalidad, ubicacion, calificacion_promedio
        FROM usuarios
        WHERE id = :id AND rol IN ('mentor', 'ambos')
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

/**
 * Verifica si un mentor ya tiene una sesión aceptada o solicitud aceptada en la misma fecha y hora.
 */
function tiene_conflicto_horario(int $mentorId, string $fecha, string $horaInicio): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*) AS total
        FROM solicitudes
        WHERE mentor_id = :mid
          AND fecha_sesion = :fecha
          AND hora_inicio = :hora
          AND estado = 'aceptada'
    ");
    $stmt->execute([
        ':mid' => $mentorId,
        ':fecha' => $fecha,
        ':hora' => $horaInicio,
    ]);
    $row = $stmt->fetch();
    return ((int) ($row['total'] ?? 0)) > 0;
}

/**
 * Obtiene todas las solicitudes y sesiones relacionadas con un usuario (como estudiante o como mentor),
 * o las solicitudes generales de demostración si se consulta como visitante.
 */
function obtener_solicitudes_agenda(?int $usuarioId = null): array
{
    $sql = "
        SELECT
            s.id,
            s.estudiante_id,
            est.nombre AS estudiante_nombre,
            est.ocupacion AS estudiante_ocupacion,
            s.mentor_id,
            men.nombre AS mentor_nombre,
            men.ocupacion AS mentor_ocupacion,
            h.materia,
            s.fecha_sesion,
            s.hora_inicio,
            s.hora_fin,
            s.modalidad,
            s.tema_especifico,
            s.mensaje_estudiante,
            s.estado,
            s.creado_en,
            ses.estado AS estado_sesion,
            ses.lugar_o_enlace,
            ses.notas_cierre
        FROM solicitudes s
        INNER JOIN usuarios est ON est.id = s.estudiante_id
        INNER JOIN usuarios men ON men.id = s.mentor_id
        INNER JOIN habilidades h ON h.id = s.habilidad_id
        LEFT JOIN sesiones ses ON ses.solicitud_id = s.id
    ";

    $params = [];
    if ($usuarioId !== null) {
        $sql .= " WHERE s.estudiante_id = :uid OR s.mentor_id = :uid";
        $params[':uid'] = $usuarioId;
    }

    $sql .= " ORDER BY s.fecha_sesion DESC, s.hora_inicio ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtiene estadísticas rápidas del sistema para mostrar en la portada.
 */
function obtener_estadisticas_sistema(): array
{
    $pdo = db();
    $totalMentores = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol IN ('mentor', 'ambos')")->fetchColumn();
    $totalMaterias = (int) $pdo->query("SELECT COUNT(DISTINCT materia) FROM habilidades")->fetchColumn();
    $totalHorarios = (int) $pdo->query("SELECT COUNT(*) FROM disponibilidades WHERE activo = 1")->fetchColumn();
    $totalSesiones = (int) $pdo->query("SELECT COUNT(*) FROM solicitudes")->fetchColumn();

    return [
        'mentores' => $totalMentores,
        'materias' => $totalMaterias,
        'horarios' => $totalHorarios,
        'solicitudes' => $totalSesiones,
    ];
}
