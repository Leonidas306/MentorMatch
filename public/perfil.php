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
$confirmacionSimulada = null;
$errorSolicitud = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorSolicitud = 'Token CSRF inválido. Recarga la página e intenta nuevamente.';
    } else {
        $nombreSolicitante = trim((string) ($_POST['nombre_solicitante'] ?? ($usuario['nombre'] ?? '')));
        $habilidadId = (int) ($_POST['habilidad_id'] ?? 0);
        $disponibilidadId = (int) ($_POST['disponibilidad_id'] ?? 0);
        $fechaSesion = trim((string) ($_POST['fecha_sesion'] ?? ''));
        $temaEspecifico = trim((string) ($_POST['tema_especifico'] ?? ''));
        $mensaje = trim((string) ($_POST['mensaje_estudiante'] ?? ''));

        $hoy = date('Y-m-d');

        if ($nombreSolicitante === '' || strlen($temaEspecifico) < 5) {
            $errorSolicitud = 'Por favor indica tu nombre y describe el tema o duda específica (mínimo 5 caracteres).';
        } elseif ($fechaSesion === '' || $fechaSesion < $hoy) {
            $errorSolicitud = 'Caso crítico validado: No se aceptan fechas anteriores al momento de solicitud (' . $hoy . ').';
        } else {
            $materiaElegida = 'Asesoría';
            foreach ($mentor['habilidades'] as $hab) {
                if ((int) $hab['id'] === $habilidadId) {
                    $materiaElegida = $hab['materia'];
                    break;
                }
            }

            $horarioElegido = null;
            foreach ($mentor['disponibilidades'] as $disp) {
                if ((int) $disp['id'] === $disponibilidadId) {
                    $horarioElegido = $disp;
                    break;
                }
            }

            if (!$horarioElegido) {
                $errorSolicitud = 'Selecciona uno de los bloques de disponibilidad válidos del mentor.';
            } elseif (tiene_conflicto_horario((int) $mentor['id'], $fechaSesion, $horarioElegido['hora_inicio'])) {
                $errorSolicitud = 'Caso crítico validado: El mentor ya tiene una sesión aceptada en esa misma fecha y hora (' . $fechaSesion . ' a las ' . $horarioElegido['hora_inicio'] . ').';
            } else {
                $confirmacionSimulada = [
                    'folio' => 'SIM-' . strtoupper(substr(md5($mentor['id'] . $fechaSesion . $temaEspecifico), 0, 6)),
                    'solicitante' => $nombreSolicitante,
                    'mentor' => $mentor['nombre'],
                    'materia' => $materiaElegida,
                    'fecha' => $fechaSesion,
                    'horario' => $horarioElegido['dia_semana'] . ' ' . $horarioElegido['hora_inicio'] . ' - ' . $horarioElegido['hora_fin'],
                    'lugar' => $horarioElegido['lugar_o_enlace'],
                    'modalidad' => $horarioElegido['modalidad'],
                    'tema' => $temaEspecifico,
                    'mensaje' => $mensaje,
                    'estado_inicial' => 'pendiente',
                ];
            }
        }
    }
}

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
                            <?= e(substr($mentor['nombre'], 0, 1)) ?>
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
                                💼 <?= e($mentor['ocupacion']) ?> · <?= e($mentor['experiencia']) ?>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Contacto: <span class="text-slate-700 font-semibold"><?= e($mentor['correo']) ?></span> · 📍 <?= e($mentor['ubicacion']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="inline-block px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 font-extrabold text-sm">
                            ★ <?= number_format((float) $mentor['calificacion_promedio'], 1) ?> / 5.0
                        </span>
                        <p class="text-[11px] text-slate-400 mt-1 capitalize">Rol: <?= e($mentor['rol'] === 'ambos' ? 'Mentor y Estudiante' : $mentor['rol']) ?></p>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Acerca del Mentor</h2>
                    <p class="text-sm text-slate-700 leading-relaxed"><?= e($mentor['biografia']) ?></p>
                </div>
            </div>

            <!-- Materias y Temas que domina -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                <h2 class="text-lg font-extrabold text-slate-900 mb-4">Materias y Habilidades Publicadas</h2>
                <div class="space-y-3">
                    <?php foreach ($mentor['habilidades'] as $hab): ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-extrabold text-slate-900 text-sm"><?= e($hab['materia']) ?></span>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-600">
                                        <?= e($hab['area']) ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 text-[11px] font-bold">
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

            <!-- Horarios Disponibles -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                <h2 class="text-lg font-extrabold text-slate-900 mb-2">Espacios Disponibles de Asesoría</h2>
                <p class="text-xs text-slate-500 mb-4">
                    Selecciona cualquiera de estos horarios en el formulario de solicitud para coordinar tu sesión.
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

        <!-- Columna derecha: Formulario de Solicitud Simulada (Entregable 1) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-sm sticky top-24">
            <div class="flex items-center justify-between gap-2 pb-3 mb-4 border-b border-slate-100">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Revisión 1 · Flujo de Solicitud</span>
                    <h2 class="text-lg font-extrabold text-slate-900">Solicitar Sesión de Asesoría</h2>
                </div>
            </div>

            <?php if ($errorSolicitud !== ''): ?>
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                    ⚠️ <?= e($errorSolicitud) ?>
                </div>
            <?php endif; ?>

            <?php if ($confirmacionSimulada !== null): ?>
                <div class="mb-5 p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded-md bg-emerald-600 text-white text-[11px] font-extrabold">
                            ✓ Solicitud Simulada Generada
                        </span>
                        <span class="text-xs font-mono font-bold text-emerald-800">Folio: <?= e($confirmacionSimulada['folio']) ?></span>
                    </div>
                    <p class="text-xs text-emerald-900 leading-relaxed">
                        Tu solicitud para <strong><?= e($confirmacionSimulada['materia']) ?></strong> con <strong><?= e($confirmacionSimulada['mentor']) ?></strong> pasó todas las validaciones de horario y fecha.
                    </p>
                    <div class="text-xs bg-white/90 rounded-xl p-3 border border-emerald-200 space-y-1">
                        <p><strong>Solicitante:</strong> <?= e($confirmacionSimulada['solicitante']) ?></p>
                        <p><strong>Fecha y bloque:</strong> <?= e($confirmacionSimulada['fecha']) ?> (<?= e($confirmacionSimulada['horario']) ?>)</p>
                        <p><strong>Modalidad / Lugar:</strong> <?= e($confirmacionSimulada['modalidad']) ?> · <?= e($confirmacionSimulada['lugar']) ?></p>
                        <p><strong>Tema específico:</strong> <?= e($confirmacionSimulada['tema']) ?></p>
                        <p><strong>Estado inicial:</strong> <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold uppercase text-[10px]">Pendiente de confirmación</span></p>
                    </div>
                    <div class="pt-1 flex items-center justify-between text-xs">
                        <a href="agenda.php" class="font-extrabold text-emerald-800 underline">Ver flujo de estados en Agenda →</a>
                    </div>
                </div>
            <?php endif; ?>

            <form id="form-solicitud" method="POST" action="perfil.php?id=<?= (int) $mentor['id'] ?>" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nombre de quien solicita *
                    </label>
                    <input
                        type="text"
                        name="nombre_solicitante"
                        required
                        placeholder="<?= e($usuario['nombre'] ?? 'Marco Ramírez Silva') ?>"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-slate-50"
                    >
                </div>

                <div>
                    <label for="select-habilidad" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Materia o Habilidad Requerida *
                    </label>
                    <select id="select-habilidad" name="habilidad_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                        <?php foreach ($mentor['habilidades'] as $hab): ?>
                            <option value="<?= (int) $hab['id'] ?>"><?= e($hab['materia']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="select-disponibilidad" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Espacio Disponible del Mentor *
                    </label>
                    <select id="select-disponibilidad" name="disponibilidad_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                        <?php foreach ($mentor['disponibilidades'] as $disp): ?>
                            <option value="<?= (int) $disp['id'] ?>">
                                <?= e($disp['dia_semana']) ?> (<?= e($disp['hora_inicio']) ?> - <?= e($disp['hora_fin']) ?>) · <?= e($disp['modalidad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="input-fecha-sesion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Fecha Propuesta para la Sesión *
                    </label>
                    <input
                        type="date"
                        id="input-fecha-sesion"
                        name="fecha_sesion"
                        required
                        min="<?= date('Y-m-d') ?>"
                        value="<?= date('Y-m-d', strtotime('+2 days')) ?>"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">
                        Validación activa: no se permiten fechas pasadas ni horarios con doble reserva.
                    </p>
                </div>

                <div>
                    <label for="input-tema" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Tema o Duda Específica *
                    </label>
                    <input
                        type="text"
                        id="input-tema"
                        name="tema_especifico"
                        required
                        minlength="5"
                        placeholder="Ej. Práctica de estructuras de datos o revisión de portafolio"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm"
                    >
                </div>

                <div>
                    <label for="input-mensaje" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Comentarios Adicionales (Opcional)
                    </label>
                    <textarea
                        id="input-mensaje"
                        name="mensaje_estudiante"
                        rows="2"
                        placeholder="Comenta tu nivel actual o qué te gustaría lograr en la sesión..."
                        class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-xs transition cursor-pointer"
                >
                    Simular Solicitud de Sesión
                </button>
            </form>
        </div>
    </div>
</section>

<?php render_footer(); ?>
