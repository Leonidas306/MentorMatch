<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$mentorId = (int) ($_GET['id'] ?? 1);
$mentor = obtener_mentor_por_id($mentorId);

if (!$mentor) {
    render_header('Mentor no encontrado', 'inicio');
    ?>
    <div class="max-w-3xl mx-auto px-4 py-16 text-center">
        <h1 class="text-2xl font-extrabold text-slate-900">El perfil de mentor solicitado no existe</h1>
        <p class="text-sm text-slate-600 mt-2">Verifica el enlace o regresa al catálogo principal para elegir un mentor activo.</p>
        <a href="index.php" class="inline-block mt-5 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold">← Volver al catálogo</a>
    </div>
    <?php
    render_footer();
    exit;
}

$usuario = usuario_actual();
$esMiPerfil = $usuario !== null && (int) $usuario['id'] === (int) $mentor['id'];
$soloMentor = $usuario !== null && $usuario['rol'] === 'mentor';
$puedeSolicitar = $usuario !== null && !$esMiPerfil && !$soloMentor && !empty($mentor['disponibilidades']) && !empty($mentor['habilidades']);
$errorSolicitud = '';

// PAQ-5: la solicitud ahora SE GUARDA en la base de datos (antes solo se simulaba).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($usuario === null) {
        flash_set('error', 'Inicia sesión para solicitar una asesoría.');
        redirigir('login.php');
    }
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorSolicitud = 'Token CSRF inválido. Recarga la página e intenta nuevamente.';
    } elseif (($_POST['accion'] ?? '') === 'guardar_ciudad') {
        // Cambio v3: el estudiante registra su ciudad para poder agendar clases presenciales.
        $resCiudad = actualizar_ciudad_usuario((int) $usuario['id'], (string) ($_POST['ubicacion'] ?? ''));
        flash_set($resCiudad['exito'] ? 'exito' : 'error', $resCiudad['exito'] ? $resCiudad['mensaje'] : $resCiudad['error']);
        redirigir('perfil.php?id=' . (int) $mentor['id'] . '#form-solicitud');
    } else {
        $resultado = crear_solicitud(
            (int) $usuario['id'],
            (int) $mentor['id'],
            (int) ($_POST['habilidad_id'] ?? 0),
            (int) ($_POST['disponibilidad_id'] ?? 0),
            trim((string) ($_POST['fecha_sesion'] ?? '')),
            (string) ($_POST['tema_especifico'] ?? ''),
            (string) ($_POST['mensaje_estudiante'] ?? '')
        );
        if ($resultado['exito']) {
            flash_set('exito', 'Solicitud enviada a ' . $mentor['nombre'] . '. Quedó en estado PENDIENTE hasta que el mentor la acepte o la rechace.');
            redirigir('agenda.php?estado=pendientes');
        }
        $errorSolicitud = $resultado['error'];
    }
}

// PAQ-10: reseñas verificadas (cada una viene de una sesión completada).
$resumenResenas = obtener_resumen_resenas((int) $mentor['id']);
$resenas = obtener_resenas_mentor((int) $mentor['id'], 20);

// El primer bloque que el usuario realmente puede pedir (los presenciales exigen la misma ciudad).
$primerBloque = null;
foreach ($mentor['disponibilidades'] as $dispInicial) {
    if ($dispInicial['modalidad'] !== 'Presencial' || ($usuario !== null && misma_ciudad((string) $usuario['ubicacion'], (string) $mentor['ubicacion']))) {
        $primerBloque = $dispInicial;
        break;
    }
}
$fechaSugerida = (string) ($_POST['fecha_sesion'] ?? ($primerBloque ? proxima_fecha_para_dia($primerBloque['dia_semana']) : date('Y-m-d', strtotime('+1 day'))));

render_header('Perfil de ' . $mentor['nombre'], 'inicio');
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-5">
        <a href="index.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900">
            ← Volver al listado de mentores
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Columna izquierda: Información del Mentor, Materias y Disponibilidad -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-slate-900 text-amber-400 font-extrabold text-2xl flex items-center justify-center">
                            <?= e(recortar_texto($mentor['nombre'], 1)) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-2xl font-extrabold text-slate-900"><?= e($mentor['nombre']) ?></h1>
                                <?php if (!empty($mentor['perfil_completo'])): ?>
                                    <span class="px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                                        ✓ Perfil Completo
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-slate-600 font-medium mt-0.5">
                                💼 <?= e(titular_publico($mentor['ocupacion'])) ?> · <?= e($mentor['experiencia']) ?>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                📍 <?= e($mentor['ubicacion']) ?> · Modalidad: <span class="text-slate-700 font-semibold"><?= e($mentor['modalidad']) ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="text-right space-y-1.5">
                        <span class="inline-block px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 font-extrabold text-sm">
                            <?= $mentor['calificacion_promedio'] === null ? 'Mentor nuevo' : '★ ' . number_format((float) $mentor['calificacion_promedio'], 1) . ' / 5.0' ?>
                        </span>
                        <?php if ((int) $mentor['total_resenas'] > 0): ?>
                            <p><a href="#resenas" class="text-[11px] font-bold text-indigo-700 underline"><?= (int) $mentor['total_resenas'] ?> <?= (int) $mentor['total_resenas'] === 1 ? 'reseña' : 'reseñas' ?></a></p>
                        <?php endif; ?>
                        <p class="text-sm font-extrabold text-emerald-700"><?= e(etiqueta_tarifa((int) $mentor['tarifa_hora'])) ?></p>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Acerca del Mentor</h2>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        <?= e(trim((string) $mentor['biografia']) !== '' ? $mentor['biografia'] : 'Este mentor aún no agrega una biografía.') ?>
                    </p>
                </div>
            </div>

            <!-- Materias y Temas que domina -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                <h2 class="text-lg font-extrabold text-slate-900 mb-4">Materias que imparte</h2>
                <div class="space-y-3">
                    <?php foreach ($mentor['habilidades'] as $hab): ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-extrabold text-slate-900 text-sm"><?= e($hab['materia']) ?></span>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-600">
                                        <?= e($hab['area']) ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-900 text-[11px] font-bold">
                                        Nivel: <?= e($hab['nivel']) ?>
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                                <?= e($hab['descripcion_tema']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Reseñas de estudiantes (PAQ-10) -->
            <div id="resenas" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs scroll-mt-24">
                <h2 class="text-lg font-extrabold text-slate-900">Reseñas de estudiantes</h2>
                <p class="text-xs text-slate-500 mt-0.5 mb-5">
                    Solo pueden reseñar quienes tomaron una sesión que el mentor marcó como completada.
                </p>
                <?php if (empty($resenas)): ?>
                    <p class="text-sm text-slate-500 bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <?= e($mentor['nombre']) ?> todavía no tiene reseñas. Las primeras aparecerán cuando sus estudiantes terminen una sesión.
                    </p>
                <?php else: ?>
                    <div class="pb-5 mb-5 border-b border-slate-100">
                        <?php render_resumen_resenas($resumenResenas); ?>
                    </div>
                    <?php render_lista_resenas($resenas); ?>
                    <?php if ($resumenResenas['total_resenas'] > count($resenas)): ?>
                        <p class="text-[11px] text-slate-400 mt-3">Se muestran las <?= count($resenas) ?> reseñas más recientes.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Horarios Disponibles -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                <h2 class="text-lg font-extrabold text-slate-900 mb-2">Horarios disponibles</h2>
                <p class="text-xs text-slate-500 mb-4">
                    Elige uno de estos bloques en el formulario de solicitud para coordinar tu sesión.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($mentor['disponibilidades'] as $disp): ?>
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 text-sm">📅 <?= e($disp['dia_semana']) ?></span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold <?= $disp['modalidad'] === 'Presencial' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-sky-50 text-sky-700 border border-sky-200' ?>">
                                    <?= e($disp['modalidad']) ?>
                                </span>
                            </div>
                            <p class="text-xs font-bold text-indigo-700 mt-1">
                                ⏰ <?= e($disp['hora_inicio']) ?> a <?= e($disp['hora_fin']) ?> hrs
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1">
                                📍 <?= e($disp['lugar_o_enlace']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Formulario de solicitud -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-sm lg:sticky lg:top-24">
            <div class="pb-3 mb-4 border-b border-slate-100">
                <h2 class="text-lg font-extrabold text-slate-900">Solicitar una asesoría</h2>
                <p class="text-xs text-slate-500 mt-0.5">El mentor recibirá tu solicitud y podrá aceptarla o rechazarla.</p>
            </div>

            <?php if ($errorSolicitud !== ''): ?>
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                    ⚠️ <?= e($errorSolicitud) ?>
                </div>
            <?php endif; ?>

            <?php if ($usuario === null): ?>
                <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 text-sm text-indigo-950 space-y-3">
                    <p>Para solicitar una asesoría con <strong><?= e($mentor['nombre']) ?></strong> necesitas una cuenta.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="login.php" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition">Iniciar sesión</a>
                        <a href="login.php?modo=registro" class="px-4 py-2 rounded-xl bg-white border border-indigo-200 text-indigo-800 text-xs font-bold hover:bg-indigo-100 transition">Crear cuenta</a>
                    </div>
                </div>
            <?php elseif ($esMiPerfil): ?>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-700 space-y-3">
                    <p>Este es tu perfil público. Así lo ven los estudiantes.</p>
                    <a href="panel.php" class="inline-block px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition">Editar en Mi Panel →</a>
                </div>
            <?php elseif ($soloMentor): ?>
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-950">
                    Tu cuenta es de tipo <strong>Mentor</strong>, por eso no puedes solicitar asesorías. Si también quieres aprender, crea una cuenta con el perfil <strong>Ambas cosas</strong>.
                </div>
            <?php elseif (!$puedeSolicitar): ?>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-700">
                    Este mentor aún no tiene materias u horarios publicados, así que todavía no se puede solicitar una sesión.
                </div>
            <?php else: ?>
                <?php
                // Cambio v3: las clases presenciales solo se agendan entre personas de la misma ciudad.
                $hayPresencial = false;
                foreach ($mentor['disponibilidades'] as $dispTmp) {
                    if ($dispTmp['modalidad'] === 'Presencial') { $hayPresencial = true; break; }
                }
                $ciudadMentor = nombre_ciudad((string) $mentor['ubicacion']);
                $ciudadUsuario = nombre_ciudad((string) $usuario['ubicacion']);
                $mismaCiudad = misma_ciudad($ciudadUsuario, $ciudadMentor);
                ?>
                <?php if ($hayPresencial): ?>
                    <?php if (!tiene_ciudad($ciudadUsuario)): ?>
                        <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-950">
                            <p><strong>¿Quieres clase presencial?</strong> Este mentor da clases presenciales en <strong><?= e($ciudadMentor) ?></strong>. Para agendarlas necesitas registrar tu ciudad. Si prefieres, también puedes elegir un horario virtual.</p>
                            <form method="POST" action="perfil.php?id=<?= (int) $mentor['id'] ?>" class="mt-3 flex flex-wrap gap-2">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="accion" value="guardar_ciudad">
                                <input type="text" name="ubicacion" required minlength="2" maxlength="80" autocomplete="off" placeholder="Tu ciudad" class="flex-1 min-w-[10rem] px-3 py-2 rounded-xl border border-amber-300 text-sm bg-white">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition cursor-pointer">Guardar mi ciudad</button>
                            </form>
                        </div>
                    <?php elseif ($mismaCiudad): ?>
                        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900">
                            📍 Los dos están en <strong><?= e($ciudadMentor) ?></strong>: puedes elegir horarios presenciales o virtuales.
                        </div>
                    <?php else: ?>
                        <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700">
                            📍 Los bloques presenciales de este mentor son en <strong><?= e($ciudadMentor) ?></strong> y tu ciudad es <strong><?= e($ciudadUsuario) ?></strong>, por eso solo puedes elegir bloques virtuales.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form id="form-solicitud" method="POST" action="perfil.php?id=<?= (int) $mentor['id'] ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div>
                        <label for="select-habilidad" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Materia *
                        </label>
                        <select id="select-habilidad" name="habilidad_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                            <?php foreach ($mentor['habilidades'] as $hab): ?>
                                <option value="<?= (int) $hab['id'] ?>" <?= (int) ($_POST['habilidad_id'] ?? 0) === (int) $hab['id'] ? 'selected' : '' ?>>
                                    <?= e($hab['materia']) ?> (<?= e($hab['nivel']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="select-disponibilidad" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Horario del mentor *
                        </label>
                        <select id="select-disponibilidad" name="disponibilidad_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                            <?php foreach ($mentor['disponibilidades'] as $disp): ?>
                                <option
                                    value="<?= (int) $disp['id'] ?>"
                                    data-dia="<?= e($disp['dia_semana']) ?>"
                                    <?= $disp['modalidad'] === 'Presencial' && !$mismaCiudad ? 'disabled' : '' ?>
                                    <?= (int) ($_POST['disponibilidad_id'] ?? 0) === (int) $disp['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($disp['dia_semana']) ?> (<?= e($disp['hora_inicio']) ?> - <?= e($disp['hora_fin']) ?>) · <?= e($disp['modalidad']) ?><?= $disp['modalidad'] === 'Presencial' ? ' en ' . e($ciudadMentor) . (!$mismaCiudad ? ' (solo si estás en esa ciudad)' : '') : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="input-fecha-sesion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Fecha de la sesión *
                        </label>
                        <input
                            type="date"
                            id="input-fecha-sesion"
                            name="fecha_sesion"
                            required
                            min="<?= date('Y-m-d') ?>"
                            value="<?= e($fechaSugerida) ?>"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">
                            La fecha debe caer en el mismo día de la semana que el horario elegido.
                        </p>
                    </div>

                    <div>
                        <label for="input-tema" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tema o duda específica *
                        </label>
                        <input
                            type="text"
                            id="input-tema"
                            name="tema_especifico"
                            required
                            minlength="5"
                            maxlength="150"
                            value="<?= e((string) ($_POST['tema_especifico'] ?? '')) ?>"
                            placeholder="Ej. Derivadas por regla de la cadena para mi examen"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm"
                        >
                    </div>

                    <div>
                        <label for="input-mensaje" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Comentarios adicionales (opcional)
                        </label>
                        <textarea
                            id="input-mensaje"
                            name="mensaje_estudiante"
                            rows="2"
                            maxlength="500"
                            placeholder="Cuéntale al mentor en qué vas y cuándo es tu examen..."
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm"
                        ><?= e((string) ($_POST['mensaje_estudiante'] ?? '')) ?></textarea>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-xs transition cursor-pointer"
                    >
                        Enviar solicitud
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php render_footer(); ?>
