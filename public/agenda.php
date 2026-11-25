<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$usuario = requerir_login();
$uid = (int) $usuario['id'];
$hoy = date('Y-m-d');
$ahora = date('H:i');

$todas = obtener_solicitudes_agenda($uid);

$grupos = [
    'pendientes' => array_values(array_filter($todas, fn($s) => $s['estado_efectivo'] === 'pendiente')),
    'proximas' => array_values(array_filter($todas, fn($s) => $s['estado_efectivo'] === 'aceptada')),
    'historial' => array_values(array_filter($todas, fn($s) => in_array($s['estado_efectivo'], ['completada', 'rechazada', 'cancelada'], true))),
    'todas' => $todas,
];

$sinResena = count(array_filter($todas, fn($s) => (int) $s['estudiante_id'] === $uid
    && $s['estado_efectivo'] === 'completada' && $s['resena_id'] === null));

$tabs = [
    'pendientes' => 'Pendientes',
    'proximas' => 'Próximas sesiones',
    'historial' => 'Historial',
    'todas' => 'Todas',
];

$tabActiva = (string) ($_GET['estado'] ?? '');
if (!isset($tabs[$tabActiva])) {
    $tabActiva = !empty($grupos['pendientes']) ? 'pendientes' : (!empty($grupos['proximas']) ? 'proximas' : 'todas');
}

$filas = $grupos[$tabActiva];
if (in_array($tabActiva, ['pendientes', 'proximas'], true)) {
    usort($filas, fn($a, $b) => [$a['fecha_sesion'], $a['hora_inicio']] <=> [$b['fecha_sesion'], $b['hora_inicio']]);
}

$volver = 'agenda.php?estado=' . $tabActiva;
$esMentor = in_array($usuario['rol'], ['mentor', 'ambos'], true);

$boton = fn(string $accion, int $id, string $texto, string $clases, string $confirmar = ''): string
    => boton_accion_sesion($accion, $id, $texto, $clases, $volver, $confirmar);

render_header('Mi Agenda', 'agenda');
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Mi Agenda</h1>
            <p class="text-sm text-slate-600 mt-1">
                Tus solicitudes y sesiones de asesoría, como mentor y como estudiante.
            </p>
        </div>
        <?php if ($usuario['rol'] !== 'mentor'): ?>
            <a href="index.php" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition">
                + Buscar un mentor
            </a>
        <?php endif; ?>
    </div>

    <?php if ($sinResena > 0): ?>
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-sm text-amber-950 flex flex-wrap items-center justify-between gap-3">
            <p>
                <strong>Tienes <?= $sinResena ?> <?= $sinResena === 1 ? 'sesión completada' : 'sesiones completadas' ?> sin reseña.</strong>
                Calificar a tu mentor es opcional, pero ayuda a otros estudiantes a elegir.
            </p>
            <a href="agenda.php?estado=historial" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition">Ir al historial</a>
        </div>
    <?php endif; ?>

    <!-- Leyenda compacta de estados; la explicación completa queda plegada. -->
    <div class="bg-white rounded-2xl border border-slate-200 px-5 py-3.5">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
            <span class="font-bold text-slate-500 uppercase tracking-wider">Estados:</span>
            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-extrabold uppercase">Pendiente</span>
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 font-extrabold uppercase">Aceptada</span>
            <span class="px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-900 font-extrabold uppercase">Completada</span>
            <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-900 font-extrabold uppercase">Rechazada / Cancelada</span>
        </div>
        <details class="mt-3 group">
            <summary class="text-xs font-bold text-indigo-700 cursor-pointer select-none">¿Cómo funcionan los estados?</summary>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3 text-xs text-slate-600 leading-relaxed">
                <p><strong class="text-amber-800">1. Pendiente:</strong> El estudiante envía la solicitud eligiendo materia, fecha futura y un horario del mentor. Todavía no bloquea el horario.</p>
                <p><strong class="text-emerald-800">2. Aceptada:</strong> El mentor confirma. Se verifica que no tenga otra sesión que se traslape y se crea el registro de la sesión.</p>
                <p><strong class="text-rose-800">3. Rechazada / Cancelada:</strong> Si el mentor rechaza, o cualquiera cancela una sesión aceptada, el horario queda libre.</p>
                <p><strong class="text-sky-800">4. Completada:</strong> Cuando el mentor da la asesoría la marca como completada y deja notas de cierre; pasa al historial de ambos.</p>
            </div>
        </details>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Pestañas por estado -->
        <div class="flex flex-wrap items-center gap-1.5 p-3 border-b border-slate-200 bg-slate-50/70">
            <?php foreach ($tabs as $clave => $titulo): ?>
                <a
                    href="agenda.php?estado=<?= e($clave) ?>"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition <?= $tabActiva === $clave ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-white hover:text-slate-900' ?>"
                >
                    <?= e($titulo) ?>
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] <?= $tabActiva === $clave ? 'bg-white/20' : 'bg-slate-200 text-slate-700' ?>"><?= count($grupos[$clave]) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($filas)): ?>
            <div class="p-10 text-center max-w-md mx-auto">
                <p class="text-base font-extrabold text-slate-900">No hay nada en esta sección</p>
                <p class="text-sm text-slate-600 mt-1">
                    <?php if ($tabActiva === 'todas'): ?>
                        <?php if ($esMentor): ?>
                            Aún no tienes solicitudes. Publica tus materias y horarios en Mi Panel para empezar a recibir solicitudes.
                        <?php else: ?>
                            Aún no has solicitado ninguna asesoría. Busca un mentor y agenda tu primera sesión.
                        <?php endif; ?>
                    <?php else: ?>
                        Cuando tengas solicitudes o sesiones en este estado aparecerán aquí.
                    <?php endif; ?>
                </p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <?php if ($esMentor): ?>
                        <a href="panel.php" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition">Ir a Mi Panel</a>
                    <?php endif; ?>
                    <?php if ($usuario['rol'] !== 'mentor'): ?>
                        <a href="index.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition">Buscar un mentor</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[11px] uppercase tracking-wider">
                            <th class="py-3 px-4 font-bold">Materia y tema</th>
                            <th class="py-3 px-4 font-bold">Con</th>
                            <th class="py-3 px-4 font-bold">Fecha y horario</th>
                            <th class="py-3 px-4 font-bold">Modalidad / Lugar</th>
                            <th class="py-3 px-4 font-bold">Estado</th>
                            <th class="py-3 px-4 font-bold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($filas as $sol): ?>
                            <?php
                            $soyMentor = (int) $sol['mentor_id'] === $uid;
                            $estado = $sol['estado_efectivo'];
                            $badgeClass = match ($estado) {
                                'aceptada' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
                                'pendiente' => 'bg-amber-100 text-amber-900 border-amber-200',
                                'completada' => 'bg-sky-100 text-sky-900 border-sky-200',
                                'rechazada', 'cancelada' => 'bg-rose-100 text-rose-900 border-rose-200',
                                default => 'bg-slate-100 text-slate-800 border-slate-200',
                            };
                            $contraparteNombre = $soyMentor ? $sol['estudiante_nombre'] : $sol['mentor_nombre'];
                            $contraparteDetalle = $soyMentor ? 'Estudiante · ' . $sol['estudiante_nivel'] : 'Mentor · ' . titular_publico($sol['mentor_ocupacion']);
                            $contraparteTelefono = $soyMentor ? $sol['estudiante_telefono'] : $sol['mentor_telefono'];
                            $yaInicio = $sol['fecha_sesion'] < $hoy || ($sol['fecha_sesion'] === $hoy && $sol['hora_inicio'] <= $ahora);
                            ?>
                            <tr class="hover:bg-slate-50/80 align-top">
                                <td class="py-3.5 px-4 max-w-xs">
                                    <p class="font-extrabold text-slate-900"><?= e($sol['materia']) ?></p>
                                    <p class="text-xs text-slate-500"><?= e($sol['tema_especifico']) ?></p>
                                    <?php if ($estado === 'pendiente' && trim((string) $sol['mensaje_estudiante']) !== ''): ?>
                                        <p class="text-[11px] text-slate-500 italic mt-1">“<?= e($sol['mensaje_estudiante']) ?>”</p>
                                    <?php endif; ?>
                                    <?php if ($estado === 'completada' && trim((string) $sol['notas_cierre']) !== ''): ?>
                                        <p class="text-[11px] text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-2 py-1 mt-1.5"><strong>Notas de cierre:</strong> <?= e($sol['notas_cierre']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($estado === 'completada' && $sol['resena_id'] !== null): ?>
                                        <div class="text-[11px] text-amber-950 bg-amber-50 border border-amber-100 rounded-lg px-2 py-1.5 mt-1.5">
                                            <p class="font-bold">
                                                <?= $soyMentor ? 'Reseña del estudiante' : 'Tu reseña' ?>
                                                <?php if ($sol['resena_calificacion'] !== null): ?>
                                                    <span class="ml-1 align-middle"><?= render_estrellas((float) $sol['resena_calificacion'], 'text-xs') ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (trim((string) $sol['resena_comentario']) !== ''): ?>
                                                <p class="mt-0.5">“<?= e($sol['resena_comentario']) ?>”</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($soyMentor): ?>
                                        <p class="font-semibold text-slate-800"><?= e($contraparteNombre) ?></p>
                                    <?php else: ?>
                                        <a href="perfil.php?id=<?= (int) $sol['mentor_id'] ?>" class="font-bold text-slate-800 hover:text-indigo-700 underline">
                                            <?= e($contraparteNombre) ?>
                                        </a>
                                    <?php endif; ?>
                                    <p class="text-[11px] text-slate-400"><?= e($contraparteDetalle) ?></p>
                                    <?php if ($estado === 'aceptada' && $contraparteTelefono !== ''): ?>
                                        <p class="text-[11px] text-emerald-700 font-semibold mt-0.5">📞 <?= e($contraparteTelefono) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-900">📅 <?= e($sol['fecha_sesion']) ?></p>
                                    <p class="text-xs text-indigo-700 font-semibold">⏰ <?= e($sol['hora_inicio']) ?> - <?= e($sol['hora_fin']) ?></p>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-bold">
                                        <?= e($sol['modalidad']) ?>
                                    </span>
                                    <?php if (!empty($sol['lugar_o_enlace']) && in_array($estado, ['aceptada', 'completada'], true)): ?>
                                        <p class="text-[11px] text-slate-500 mt-1">📍 <?= e($sol['lugar_o_enlace']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full border text-xs font-extrabold uppercase <?= $badgeClass ?>">
                                        <?= e($estado) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap items-start gap-1.5">
                                        <?php if ($estado === 'pendiente' && $soyMentor): ?>
                                            <?= $boton('aceptar', (int) $sol['id'], 'Aceptar', 'bg-emerald-600 hover:bg-emerald-700 text-white') ?>
                                            <?= $boton('rechazar', (int) $sol['id'], 'Rechazar', 'bg-white hover:bg-rose-50 text-rose-700 border border-rose-200', '¿Rechazar esta solicitud?') ?>
                                        <?php elseif ($estado === 'pendiente'): ?>
                                            <?= $boton('cancelar', (int) $sol['id'], 'Cancelar solicitud', 'bg-white hover:bg-rose-50 text-rose-700 border border-rose-200', '¿Cancelar tu solicitud?') ?>
                                        <?php elseif ($estado === 'aceptada'): ?>
                                            <?php if ($soyMentor && $yaInicio): ?>
                                                <details class="w-56">
                                                    <summary class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white cursor-pointer select-none">Marcar completada</summary>
                                                    <form method="POST" action="accion_sesion.php" class="mt-2 space-y-2">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="accion" value="completar">
                                                        <input type="hidden" name="solicitud_id" value="<?= (int) $sol['id'] ?>">
                                                        <input type="hidden" name="volver" value="<?= e($volver) ?>">
                                                        <textarea name="notas_cierre" rows="2" maxlength="500" placeholder="Notas de cierre (qué se vio, tarea…)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs"></textarea>
                                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 cursor-pointer">Guardar y completar</button>
                                                    </form>
                                                </details>
                                            <?php elseif ($yaInicio): ?>
                                                <span class="text-[11px] text-slate-500">Esperando el cierre del mentor</span>
                                            <?php endif; ?>
                                            <?php if ($sol['fecha_sesion'] >= $hoy): ?>
                                                <?= $boton('cancelar', (int) $sol['id'], 'Cancelar', 'bg-white hover:bg-rose-50 text-rose-700 border border-rose-200', '¿Cancelar esta sesión? El horario quedará libre.') ?>
                                            <?php endif; ?>
                                        <?php elseif ($estado === 'completada' && !$soyMentor && $sol['resena_id'] === null): ?>
                                            <details class="w-64">
                                                <summary class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white cursor-pointer select-none">Dejar reseña (opcional)</summary>
                                                <form method="POST" action="accion_sesion.php" class="mt-2 space-y-2">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="accion" value="resenar">
                                                    <input type="hidden" name="solicitud_id" value="<?= (int) $sol['id'] ?>">
                                                    <input type="hidden" name="volver" value="<?= e($volver) ?>">
                                                    <p class="text-[11px] font-bold text-slate-600">¿Cómo estuvo la asesoría?</p>
                                                    <?= render_selector_estrellas('estrellas-' . (int) $sol['id']) ?>
                                                    <textarea name="comentario" rows="3" maxlength="500" placeholder="Comenta cómo te fue con <?= e($sol['mentor_nombre']) ?> (opcional)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs"></textarea>
                                                    <p class="text-[10px] text-slate-400">Se publica en el perfil del mentor con tu nombre abreviado. No podrás editarla después.</p>
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 cursor-pointer">Publicar reseña</button>
                                                </form>
                                            </details>
                                        <?php else: ?>
                                            <span class="text-[11px] text-slate-400">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php render_footer(); ?>
