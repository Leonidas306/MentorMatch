<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

/**
 * Panel del mentor.
 * Responde solicitudes pendientes
 * Publica/elimina horarios
 * Publica/elimina materias
 * Edita su perfil público.
 */
$usuario = requerir_login();
if (!in_array($usuario['rol'], ['mentor', 'ambos'], true)) {
    flash_set('error', 'El panel es solo para mentores. Tu cuenta es de estudiante.');
    redirigir('index.php');
}
$uid = (int) $usuario['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash_set('error', 'La sesión del formulario caducó. Intenta de nuevo.');
        redirigir('panel.php');
    }
    // Redirige a la página después de guardar.
    $accion = (string) ($_POST['accion'] ?? '');
    $ancla = '';

    switch ($accion) {
        case 'guardar_perfil':
            $ancla = '#perfil';
            $resultado = actualizar_perfil_mentor($uid, $_POST);
            break;
        case 'agregar_materia':
            $ancla = '#materias';
            $resultado = crear_habilidad(
                $uid,
                (string) ($_POST['materia'] ?? ''),
                (string) ($_POST['area'] ?? ''),
                (string) ($_POST['nivel'] ?? ''),
                (string) ($_POST['descripcion_tema'] ?? '')
            );
            break;
        case 'eliminar_materia':
            $ancla = '#materias';
            $resultado = eliminar_habilidad($uid, (int) ($_POST['habilidad_id'] ?? 0));
            break;
        case 'agregar_horario':
            $ancla = '#horarios';
            $resultado = crear_disponibilidad(
                $uid,
                (string) ($_POST['dia_semana'] ?? ''),
                (string) ($_POST['hora_inicio'] ?? ''),
                (string) ($_POST['hora_fin'] ?? ''),
                (string) ($_POST['modalidad'] ?? ''),
                (string) ($_POST['lugar_o_enlace'] ?? '')
            );
            break;
        case 'eliminar_horario':
            $ancla = '#horarios';
            $resultado = eliminar_disponibilidad($uid, (int) ($_POST['disponibilidad_id'] ?? 0));
            break;
        default:
            $resultado = resultado_error('Acción no válida.');
    }

    flash_set($resultado['exito'] ? 'exito' : 'error', $resultado['exito'] ? $resultado['mensaje'] : $resultado['error']);
    redirigir('panel.php' . $ancla);
}

$mentor = obtener_mentor_por_id($uid);
$habilidades = $mentor['habilidades'];
$horarios = $mentor['disponibilidades'];

$solicitudes = obtener_solicitudes_agenda($uid);
$pendientes = array_values(array_filter($solicitudes, fn($s) => (int) $s['mentor_id'] === 
$uid && $s['estado_efectivo'] === 'pendiente'));
usort($pendientes, fn($a, $b) => [$a['fecha_sesion'], $a['hora_inicio']] <=>
 [$b['fecha_sesion'], $b['hora_inicio']]);
$proximas = array_values(array_filter($solicitudes, fn($s) => (int) $s['mentor_id'] === 
$uid && $s['estado_efectivo'] === 'aceptada'));
$completadas = array_filter($solicitudes, fn($s) => (int) $s['mentor_id'] === $uid && 
$s['estado_efectivo'] === 'completada');

// Resumen de reseñas.
$resumenResenas = obtener_resumen_resenas($uid);
$resenasRecibidas = obtener_resenas_mentor($uid, 20);

$checklist = [
    ['Biografía escrita', trim((string) $mentor['biografia']) !== '', '#perfil'],
    ['Al menos 1 materia publicada', !empty($habilidades), '#materias'],
    ['Al menos 1 horario publicado', !empty($horarios), '#horarios'],
];
$perfilCompleto = !in_array(false, array_column($checklist, 1), true);

$estiloInput = 'w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white
 focus:outline-none focus:ring-2 focus:ring-indigo-500';
$estiloLabel = 'block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1';

render_header('Mi Panel de Mentor', 'panel');
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Mi Panel 
                de Mentor</h1>
            <p class="text-sm text-slate-600 mt-1">Administra tus solicitudes, tus horarios,
                 tus materias y tu perfil público.</p>
        </div>
        <div class="flex gap-2">
            <a href="perfil.php?id=<?= $uid ?>" class="px-4 py-2.5 rounded-xl bg-white 
            border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50
             transition">Ver mi perfil público</a>
            <a href="agenda.php" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white 
            text-xs font-bold hover:bg-slate-800 transition">Mi agenda completa</a>
        </div>
    </div>

    <!-- Perfil completo -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-5 bg-white rounded-2xl border <?= $perfilCompleto ? 
        'border-emerald-200' : 'border-amber-200' ?> p-5">
            <h2 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                <?= $perfilCompleto ? '✓ Perfil completo' : 'Completa tu perfil para 
                aparecer en el directorio' ?>
            </h2>
            <ul class="mt-3 space-y-1.5 text-xs">
                <?php foreach ($checklist as [$texto, $hecho, $enlace]): ?>
                    <li class="flex items-center gap-2 <?= $hecho ? 'text-emerald-700' : 
                    'text-slate-600' ?>">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center 
                        text-[10px] font-bold <?= $hecho ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' ?>"><?= $hecho ? '✓' : '·' ?></span>
                        <?php if ($hecho): ?>
                            <?= e($texto) ?>
                        <?php else: ?>
                            <a href="<?= e($enlace) ?>" class="underline font-semibold 
                            hover:text-indigo-700"><?= e($texto) ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="text-[11px] text-slate-400 mt-3">Los estudiantes solo ven mentores
                 que tienen al menos una materia y un horario publicados.</p>
        </div>
        <div class="lg:col-span-7 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-amber-200 p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Pendientes</p>
                <p class="text-3xl font-extrabold text-amber-700 mt-1"><?= 
                count($pendientes) ?></p>
                <p class="text-[11px] text-slate-400 mt-1">por responder</p>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-200 p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Próximas</p>
                <p class="text-3xl font-extrabold text-emerald-700 mt-1"><?= count($proximas) ?></p>
                <p class="text-[11px] text-slate-400 mt-1">sesiones aceptadas</p>
            </div>
            <div class="bg-white rounded-2xl border border-sky-200 p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Completadas</p>
                <p class="text-3xl font-extrabold text-sky-700 mt-1"><?= count($completadas) ?></p>
                <p class="text-[11px] text-slate-400 mt-1">asesorías impartidas</p>
            </div>
            <a href="#resenas" class="block bg-white rounded-2xl border border-amber-200 
            p-5 hover:bg-amber-50/40 transition">
                <p class="text-[11px] font-bold uppercase tracking-wider 
                text-slate-500">Calificación</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-1"><?=
                 $resumenResenas['promedio'] === null ? '—' : e(number_format((float) 
                 $resumenResenas['promedio'], 1)) ?></p>
                <p class="text-[11px] text-slate-400 mt-1"><?= (int) $resumenResenas
                ['total_resenas'] ?> <?= (int) $resumenResenas['total_resenas'] 
                === 1 ? 'reseña' : 'reseñas' ?></p>
            </a>
        </div>
    </div>

    <!-- Solicitudes pendientes -->
    <div id="solicitudes" class="bg-white rounded-2xl border border-slate-200 shadow-xs">
        <div class="p-5 border-b border-slate-200">
            <h2 class="text-base font-extrabold text-slate-900">Solicitudes pendientes 
                (<?= count($pendientes) ?>)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Al aceptar una, 
                se rechazan automáticamente otras pendientes que choquen con ese horario.</p>
        </div>
        <?php if (empty($pendientes)): ?>
            <p class="p-6 text-sm text-slate-500">No tienes solicitudes pendientes. 
                Cuando un estudiante te solicite una asesoría aparecerá aquí.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-200">
                <?php foreach ($pendientes as $sol): ?>
                    <li class="p-5 flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 space-y-1">
                            <p class="font-extrabold text-slate-900 text-sm"><?= 
                            e($sol['materia']) ?> <span class="font-medium text-slate-400"
                            >·</span> <span class="font-semibold text-slate-700"><?= 
                            e($sol['estudiante_nombre']) ?></span> <span class="text-[11px]
                             font-medium text-slate-400">(<?= e($sol['estudiante_nivel']) ?>)</span></p>
                            <p class="text-xs text-slate-600">Tema: <?= e($sol['tema_especifico']) ?></p>
                            <?php if (trim((string) $sol['mensaje_estudiante']) !== ''): ?>
                                <p class="text-[11px] text-slate-500 italic">“<?= e($sol['mensaje_estudiante']) ?>”</p>
                            <?php endif; ?>
                            <p class="text-xs font-bold text-indigo-700">📅 <?= e($sol['fecha_sesion']) ?> · ⏰ <?= e($sol['hora_inicio']) ?> - <?= e($sol['hora_fin']) ?> · <?= e($sol['modalidad']) ?></p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <?= boton_accion_sesion('aceptar', (int) $sol['id'], 'Aceptar', 
                            'bg-emerald-600 hover:bg-emerald-700 text-white', 'panel.php') ?>
                            <?= boton_accion_sesion('rechazar', (int) $sol['id'], 'Rechazar', 
                            'bg-white hover:bg-rose-50 text-rose-700 border border-rose-200', 
                            'panel.php', '¿Rechazar esta solicitud?') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Horarios -->
        <div id="horarios" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5 scroll-mt-24">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Mis horarios disponibles (<?= count($horarios) ?>)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Bloques semanales. El estudiante elige uno y una fecha que caiga en ese día.</p>
            </div>

            <?php if (empty($horarios)): ?>
                <p class="text-sm text-slate-500 bg-slate-50 rounded-xl p-4 border
                 border-slate-200">Aún no publicas horarios. Agrega el primero abajo.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($horarios as $disp): ?>
                        <li class="flex items-center justify-between gap-3 p-3 rounded-xl border 
                        border-slate-200 bg-slate-50/60">
                            <div class="min-w-0">
                                <p class="text-sm font-extrabold text-slate-900"><?= e($disp['dia_semana']) ?> · <?= 
                                e($disp['hora_inicio']) ?>–<?= e($disp['hora_fin']) ?>
                                    <span class="ml-1 px-2 py-0.5 rounded text-[10px] font-bold <?= 
                                    $disp['modalidad'] === 'Presencial' ? 'bg-emerald-50 text-emerald-700 border 
                                    border-emerald-200' : 'bg-sky-50 text-sky-700 border border-sky-200' ?>"><?= 
                                    e($disp['modalidad']) ?></span>
                                </p>
                                <p class="text-[11px] text-slate-500 truncate">📍 <?= e($disp['lugar_o_enlace']) ?></p>
                            </div>
                            <form method="POST" action="panel.php" onsubmit="return confirm('¿Eliminar este horario? 
                            Las solicitudes ya hechas se conservan.');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="accion" value="eliminar_horario">
                                <input type="hidden" name="disponibilidad_id" value="<?= (int) $disp['id'] ?>">
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white
                                 hover:bg-rose-50 text-rose-700 border border-rose-200 cursor-pointer">Eliminar</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" action="panel.php" class="space-y-3 pt-4 border-t border-slate-100">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="agregar_horario">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-indigo-700">Agregar horario</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="h-dia" class="<?= $estiloLabel ?>">Día</label>
                        <select id="h-dia" name="dia_semana" required class="<?= $estiloInput ?>">
                            <?php foreach (dias_semana() as $dia): ?>
                                <option value="<?= e($dia) ?>"><?= e($dia) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="h-inicio" class="<?= $estiloLabel ?>">Inicio</label>
                        <input type="time" id="h-inicio" name="hora_inicio" required value="17:00" class="<?= $estiloInput ?>">
                    </div>
                    <div>
                        <label for="h-fin" class="<?= $estiloLabel ?>">Fin</label>
                        <input type="time" id="h-fin" name="hora_fin" required value="18:00" class="<?= $estiloInput ?>">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="h-mod" class="<?= $estiloLabel ?>">Modalidad</label>
                        <select id="h-mod" name="modalidad" required class="<?= $estiloInput ?>">
                            <option value="Virtual">Virtual</option>
                            <option value="Presencial">Presencial</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="h-lugar" class="<?= $estiloLabel ?>">Lugar o enlace 
                            <span class="normal-case font-medium text-slate-400">(obligatorio si es presencial)</span></label>
                        <input type="text" id="h-lugar" name="lugar_o_enlace" 
                        maxlength="120" placeholder="Ej. Biblioteca central / Google Meet" class="<?= $estiloInput ?>">
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 
                hover:bg-indigo-700 text-white text-xs font-extrabold transition
                 cursor-pointer">Publicar horario</button>
            </form>
        </div>

        <!-- Materias -->
        <div id="materias" class="bg-white rounded-2xl border border-slate-200 shadow-xs 
        p-6 space-y-5 scroll-mt-24">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Mis materias (<?= count($habilidades) ?>)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Las materias que impartes y el nivel educativo al que las enseñas.</p>
            </div>

            <?php if (empty($habilidades)): ?>
                <p class="text-sm text-slate-500 bg-slate-50 rounded-xl p-4 
                border border-slate-200">Aún no publicas materias. Agrega la primera abajo.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($habilidades as $hab): ?>
                        <li class="flex items-start justify-between gap-3 p-3 
                        rounded-xl border border-slate-200 bg-slate-50/60">
                            <div class="min-w-0">
                                <p class="text-sm font-extrabold text-slate-900"><?= 
                                e($hab['materia']) ?></p>
                                <p class="text-[11px] text-slate-500"><?= 
                                e($hab['area']) ?> · <span class="font-bold text-indigo-700"><?= 
                                e($hab['nivel']) ?></span></p>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2"><?= 
                                e($hab['descripcion_tema']) ?></p>
                            </div>
                            <form method="POST" action="panel.php" class="shrink-0" 
                            onsubmit="return confirm('¿Eliminar esta materia?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="accion" value="eliminar_materia">
                                <input type="hidden" name="habilidad_id" value="<?= (int) $hab['id'] ?>">
                                <button type="submit" class="px-3 py-1.5 rounded-lg 
                                text-xs font-bold bg-white hover:bg-rose-50 text-rose-700 
                                border border-rose-200 cursor-pointer">Eliminar</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" action="panel.php" class="space-y-3 pt-4 border-t border-slate-100">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="agregar_materia">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-indigo-700">Agregar materia</h3>
                <div>
                    <label for="m-materia" class="<?= $estiloLabel ?>">Nombre de la materia</label>
                    <input type="text" id="m-materia" name="materia" required minlength="3"
                     maxlength="80" placeholder="Ej. Álgebra para secundaria" class="<?= $estiloInput ?>">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="m-area" class="<?= $estiloLabel ?>">Categoría</label>
                        <select id="m-area" name="area" required class="<?= $estiloInput ?>">
                            <?php foreach (lista_areas() as $area): ?>
                                <option value="<?= e($area) ?>"><?= e($area) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="m-nivel" class="<?= $estiloLabel ?>">Nivel educativo</label>
                        <select id="m-nivel" name="nivel" required class="<?= $estiloInput ?>">
                            <?php foreach (niveles_habilidad() as $nivel): ?>
                                <option value="<?= e($nivel) ?>" <?= $nivel === 'Preparatoria' ? 'selected' : '' ?>><?= 
                                    e($nivel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="m-desc" class="<?= $estiloLabel ?>">Temas que cubres</label>
                    <textarea id="m-desc" name="descripcion_tema" required rows="2" 
                    minlength="10" maxlength="300" placeholder="Ej. Ecuaciones, factorización, 
                    funciones y repaso de exámenes." class="<?= $estiloInput ?>"></textarea>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 
                hover:bg-indigo-700 text-white text-xs font-extrabold transition 
                cursor-pointer">Publicar materia</button>
            </form>
        </div>
    </div>

    <!-- Reseñas recibidas-->
    <div id="resenas" class="bg-white rounded-2xl border border-slate-200 shadow-xs 
    p-6 scroll-mt-24">
        <h2 class="text-base font-extrabold text-slate-900">Reseñas que he recibido</h2>
        <p class="text-xs text-slate-500 mt-0.5 mb-5">Vienen de sesiones completadas, 
            así que son verificadas. Son visibles en tu perfil público y no se pueden editar.</p>
        <?php if (empty($resenasRecibidas)): ?>
            <p class="text-sm text-slate-500 bg-slate-50 rounded-xl p-4 border 
            border-slate-200">Aún no tienes reseñas. Cuando marques una sesión como completada,
             tu estudiante podrá calificarla (opcional).</p>
        <?php else: ?>
            <div class="pb-5 mb-5 border-b border-slate-100">
                <?php render_resumen_resenas($resumenResenas); ?>
            </div>
            <?php render_lista_resenas($resenasRecibidas); ?>
        <?php endif; ?>
    </div>

    <!-- Perfil -->
    <div id="perfil" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 scroll-mt-24">
        <h2 class="text-base font-extrabold text-slate-900">Mi perfil público</h2>
        <p class="text-xs text-slate-500 mt-0.5 mb-5">Esto es lo que ven los estudiantes en el directorio. 
            Tu correo no se muestra públicamente.</p>

        <form method="POST" action="panel.php" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="guardar_perfil">

            <div class="md:col-span-8">
                <label for="p-titular" class="<?= $estiloLabel ?>">Titular del perfil *</label>
                <input type="text" id="p-titular" name="ocupacion" required minlength="3"
                 maxlength="120" value="<?= e($usuario['ocupacion'] === 'Mentor de regularización' ? '' : $usuario['ocupacion']) ?>" autocomplete="off" placeholder="Ej. Ing. en Sistemas & Tutor de Matemáticas" class="<?= $estiloInput ?>">
            </div>
            <div class="md:col-span-2">
                <label for="p-anios" class="<?= $estiloLabel ?>">Años dando clases</label>
                <input type="number" id="p-anios" name="anios_experiencia" min="0" max="60" 
                autocomplete="off" value="<?= extraer_anios_experiencia((string) $usuario['experiencia']) ?>" class="<?= $estiloInput ?>">
            </div>
            <div class="md:col-span-2">
                <label for="p-tarifa" class="<?= $estiloLabel ?>">Tarifa por hora ($)</label>
                <input type="number" id="p-tarifa" name="tarifa_hora" min="0" max="5000" step="10"
                 value="<?= (int) $usuario['tarifa_hora'] > 0 ? (int) $usuario['tarifa_hora'] : '' 
                 ?>" autocomplete="off" class="<?= $estiloInput ?>">
            </div>

            <div class="md:col-span-12 -mt-2 p-3 rounded-xl bg-amber-50 border border-amber-200 
            text-xs text-amber-900">
                ⚠ <strong>Tarifa por hora:</strong> si dejas este espacio en blanco, 
                tus asesorías quedarán asignadas como <strong>gratuitas</strong>.
            </div>

            <div class="md:col-span-12">
                <label for="p-bio" class="<?= $estiloLabel ?>">Biografía</label>
                <textarea id="p-bio" name="biografia" rows="3" autocomplete="off" maxlength="600" 
                placeholder="Cuéntales a los estudiantes cómo das tus clases, qué materias dominas 
                y qué resultados buscas…" class="<?= $estiloInput ?>"><?= e((string) $usuario['biografia']) ?></textarea>
            </div>

            <div class="md:col-span-4">
                <label for="p-mod" class="<?= $estiloLabel ?>">Preferencia</label>
                <select id="p-mod" name="modalidad" autocomplete="off" class="<?= $estiloInput ?>">
                    <?php foreach (['Ambas' => 'Virtual y presencial', 'Virtual' => 'Solo virtual', 
                    'Presencial' => 'Solo presencial'] as $valor => $texto): ?>
                        <option value="<?= e($valor) ?>" <?= $usuario['modalidad'] === $valor ? 
                        'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-4">
                <label for="p-ubi" class="<?= $estiloLabel ?>">Ciudad</label>
                <input type="text" id="p-ubi" name="ubicacion" maxlength="80" autocomplete="off"
                 value="<?= e($usuario['ubicacion']) ?>" class="<?= $estiloInput ?>">
            </div>
            <div class="md:col-span-4">
                <label for="p-tel" class="<?= $estiloLabel ?>">WhatsApp o teléfono</label>
                <input type="tel" id="p-tel" name="telefono" maxlength="20" autocomplete="off" 
                value="<?= e($usuario['telefono']) ?>" placeholder="Opcional" class="<?= $estiloInput ?>">
            </div>

            <div class="md:col-span-12">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 
                hover:bg-slate-800 text-white text-xs font-extrabold transition 
                cursor-pointer">Guardar perfil</button>
            </div>
        </form>
    </div>
</section>

<?php render_footer(); ?>
