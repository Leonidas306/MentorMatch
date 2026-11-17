<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$usuario = usuario_actual();
$filtroVista = ($_GET['vista'] ?? 'todas') === 'mias' && $usuario ? 'mias' : 'todas';
$solicitudes = obtener_solicitudes_agenda($filtroVista === 'mias' ? (int) $usuario['id'] : null);

render_header('Agenda y Estados de Sesión', 'agenda');
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Encabezado y selector de vista -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Requisito Revisión 1 · Modelo de Estados</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Agenda de Sesiones y Ciclo de Estados</h1>
            <p class="text-sm text-slate-600 mt-1">
                Visualización del flujo de solicitudes (<code class="font-bold">pendiente</code>, <code class="font-bold">aceptada</code>, <code class="font-bold">rechazada</code>, <code class="font-bold">cancelada</code>) y sesiones confirmadas.
            </p>
        </div>

        <?php if ($usuario): ?>
            <div class="flex items-center gap-2 bg-white p-1.5 rounded-xl border border-slate-200">
                <a
                    href="agenda.php?vista=todas"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= $filtroVista === 'todas' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' ?>"
                >
                    Todas las de demostración
                </a>
                <a
                    href="agenda.php?vista=mias"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= $filtroVista === 'mias' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' ?>"
                >
                    Mis sesiones (<?= e($usuario['nombre']) ?>)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tarjetas explicativas de los Estados de una Sesión -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-amber-200 shadow-2xs">
            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-xs font-extrabold uppercase">1. Pendiente</span>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                El estudiante o aprendiz envía la solicitud eligiendo materia, fecha futura y bloque disponible del mentor. Aún no bloquea el horario definitivamente.
            </p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-emerald-200 shadow-2xs">
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 text-xs font-extrabold uppercase">2. Aceptada / Programada</span>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                El mentor confirma la solicitud. Se verifica que no exista otra sesión simultánea para el mismo mentor y se crea el registro en <code class="font-bold">sesiones</code>.
            </p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-rose-200 shadow-2xs">
            <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-900 text-xs font-extrabold uppercase">3. Rechazada / Cancelada</span>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                Si el mentor rechaza o cualquiera de los implicados cancela la sesión, el espacio horario queda liberado para nuevas solicitudes.
            </p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-sky-200 shadow-2xs">
            <span class="px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-900 text-xs font-extrabold uppercase">4. Completada (Historial)</span>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                Una vez impartida la asesoría, pasa al historial de sesiones con notas de cierre para consulta de ambos perfiles.
            </p>
        </div>
    </div>

    <!-- Tabla de Solicitudes y Sesiones Registradas en Semilla -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-extrabold text-slate-900">
                Solicitudes y Sesiones Registradas (<?= count($solicitudes) ?>)
            </h2>
            <span class="text-xs text-slate-500">Datos cargados desde SQLite (<code class="font-mono">solicitudes</code> + <code class="font-mono">sesiones</code>)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[11px] uppercase tracking-wider">
                        <th class="py-3 px-4 font-bold">Materia / Habilidad y Tema</th>
                        <th class="py-3 px-4 font-bold">Mentor</th>
                        <th class="py-3 px-4 font-bold">Solicitante</th>
                        <th class="py-3 px-4 font-bold">Fecha y Horario</th>
                        <th class="py-3 px-4 font-bold">Modalidad / Lugar</th>
                        <th class="py-3 px-4 font-bold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($solicitudes as $sol): ?>
                        <?php
                        $badgeClass = match ($sol['estado']) {
                            'aceptada' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
                            'pendiente' => 'bg-amber-100 text-amber-900 border-amber-200',
                            'rechazada', 'cancelada' => 'bg-rose-100 text-rose-900 border-rose-200',
                            default => 'bg-slate-100 text-slate-800 border-slate-200',
                        };
                        ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-4">
                                <p class="font-extrabold text-slate-900"><?= e($sol['materia']) ?></p>
                                <p class="text-xs text-slate-500"><?= e($sol['tema_especifico']) ?></p>
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="perfil.php?id=<?= (int) $sol['mentor_id'] ?>" class="font-bold text-slate-800 hover:text-indigo-700 underline">
                                    <?= e($sol['mentor_nombre']) ?>
                                </a>
                                <p class="text-[11px] text-slate-400"><?= e($sol['mentor_ocupacion']) ?></p>
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="font-semibold text-slate-800"><?= e($sol['estudiante_nombre']) ?></p>
                                <p class="text-[11px] text-slate-400"><?= e($sol['estudiante_ocupacion']) ?></p>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <p class="font-bold text-slate-900">📅 <?= e($sol['fecha_sesion']) ?></p>
                                <p class="text-xs text-indigo-700 font-semibold">⏰ <?= e($sol['hora_inicio']) ?> - <?= e($sol['hora_fin']) ?></p>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-bold">
                                    <?= e($sol['modalidad']) ?>
                                </span>
                                <?php if (!empty($sol['lugar_o_enlace'])): ?>
                                    <p class="text-[11px] text-slate-500 mt-1">📍 <?= e($sol['lugar_o_enlace']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full border text-xs font-extrabold uppercase <?= $badgeClass ?>">
                                    <?= e($sol['estado']) ?>
                                </span>
                                <?php if (!empty($sol['estado_sesion'])): ?>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Sesión: <strong class="capitalize"><?= e($sol['estado_sesion']) ?></strong>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php render_footer(); ?>
