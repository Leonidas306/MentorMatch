<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

/**
 * PAQ-6: único punto que cambia el estado de una solicitud/sesión (solo por POST).
 * Acciones: aceptar | rechazar (mentor), cancelar (estudiante o mentor), completar (mentor),
 * resenar (estudiante de una sesión completada; PAQ-10),
 * guardar_ciudad (v3: ciudad del usuario para clases presenciales).
 * La validación de "quién puede hacer qué" vive en repository.php (responder_solicitud,
 * cancelar_solicitud, completar_sesion), este archivo solo valida CSRF y redirige.
 */
$usuario = requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('agenda.php');
}

// Solo se permite volver a agenda.php o panel.php (evita redirecciones abiertas).
$volver = (string) ($_POST['volver'] ?? 'agenda.php');
if (!preg_match('/^((agenda|panel)\.php(\?estado=[a-z]+)?|index\.php(\?[A-Za-z0-9=&%_+.\-]*)?)$/', $volver)) {
    $volver = 'agenda.php';
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    flash_set('error', 'La sesión del formulario caducó. Intenta de nuevo.');
    redirigir($volver);
}

$accion = (string) ($_POST['accion'] ?? '');
$solicitudId = (int) ($_POST['solicitud_id'] ?? 0);
$uid = (int) $usuario['id'];

// PAQ-10: la calificación es opcional; si llega vacía se guarda sin estrellas.
$calificacionCruda = trim((string) ($_POST['calificacion'] ?? ''));
$calificacion = $calificacionCruda === '' ? null : (ctype_digit($calificacionCruda) ? (int) $calificacionCruda : 0);

$resultado = match ($accion) {
    'aceptar', 'rechazar' => responder_solicitud($solicitudId, $uid, $accion),
    'cancelar' => cancelar_solicitud($solicitudId, $uid),
    'completar' => completar_sesion($solicitudId, $uid, (string) ($_POST['notas_cierre'] ?? '')),
    'guardar_ciudad' => actualizar_ciudad_usuario($uid, (string) ($_POST['ubicacion'] ?? '')),
    'resenar' => crear_resena($uid, $solicitudId, $calificacion, (string) ($_POST['comentario'] ?? '')),
    default => resultado_error('Acción no válida.'),
};

flash_set($resultado['exito'] ? 'exito' : 'error', $resultado['exito'] ? $resultado['mensaje'] : $resultado['error']);
redirigir($volver);
