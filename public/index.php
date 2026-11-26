<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/bootstrap.php';

$busqueda=trim((string)($_GET['q']??''));
$areaSeleccionada=trim((string)($_GET['area']??''));
$materiaSeleccionada=trim((string) ($_GET['materia'] ?? ''));
$modalidadSeleccionada = trim((string) ($_GET['modalidad'] ?? ''));
$nivelSeleccionado = trim((string) ($_GET['nivel'] ?? ''));

$categorias=obtener_categorias_con_conteo();
$usuario = usuario_actual();
// Cambio v3: con la ciudad del usuario se pueden mostrar solo mentores presenciales de su ciudad.
$ciudadUsuario = $usuario ? nombre_ciudad((string) $usuario['ubicacion']) : '';
$mentores = buscar_mentores($busqueda, $materiaSeleccionada, $modalidadSeleccionada, $areaSeleccionada, $nivelSeleccionado, $ciudadUsuario);
$estadisticas = obtener_estadisticas_sistema();

function icono_categoria_svg(string $icono): string
{
    return match ($icono) {
        'atom' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><ellipse cx="12" cy="12" rx="10" ry="4.5" transform="rotate(30 12 12)"/><ellipse cx="12" cy="12" rx="10" ry="4.5" transform="rotate(150 12 12)"/></svg>',
        'math' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h3l2.5 7 3.5-14h7"/><path d="M15 11l5 5"/><path d="M20 11l-5 5"/></svg>',
        'code' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/><line x1="14" y1="4" x2="10" y2="20"/></svg>',
        'design' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>',
        'finance' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><path d="M3 20h18"/></svg>',
        'leaf' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>',
        'book' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'lang' => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><text x="12" y="12.5" text-anchor="middle" font-size="6.5" font-weight="700" fill="currentColor" stroke="none">A/EN</text></svg>',
        default => '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>',
    };
}

render_header('Catálogo y búsqueda de tutores','inicio');
?>

<section class="bg-gradient-to-br from-indigo-950 via-slate-900 to-violet-950 text-white pt-12 pb-20 sm:pt-16 sm:pb-24 border-b border-indigo-900/40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-4">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                    Regulariza tus materias con la ayuda de un mentor, <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-teal-300">a tu propio ritmo</span>
                </h1>
                <p class="text-slate-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                    Encuentra mentores de matemáticas, física, química, biología, español, inglés y programación. Revisa su experiencia, consulta sus horarios libres y agenda tu asesoría.
                </p>
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="#barra-categorias" class="px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white font-bold text-sm shadow-sm transition">
                        Explorar por Categoría
                    </a>
                    <?php if ($usuario && in_array($usuario['rol'], ['mentor', 'ambos'], true)): ?>
                        <a href="panel.php" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/15 text-white border border-white/15 font-semibold text-sm transition">
                            Ir a Mi Panel de Mentor
                        </a>
                    <?php elseif ($usuario): ?>
                        <a href="agenda.php" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/15 text-white border border-white/15 font-semibold text-sm transition">
                            Ver Mi Agenda
                        </a>
                    <?php else: ?>
                        <a href="login.php?modo=registro" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/15 text-white border border-white/15 font-semibold text-sm transition">
                            Crear mi cuenta
                        </a>
                        <a href="login.php" class="px-4 py-3 rounded-xl text-indigo-300 hover:text-white font-semibold text-sm underline">
                            Ya tengo cuenta →
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-5 grid grid-cols-2 gap-3 sm:gap-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-white/5 backdrop-blur border border-white/10">
                    <span class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Mentores Activos</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-white mt-1"><?= (int) $estadisticas['mentores'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">Con materias y horarios publicados</p>
                </div>
                <div class="p-4 sm:p-5 rounded-2xl bg-white/5 backdrop-blur border border-white/10">
                    <span class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Materias Activas</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-teal-300 mt-1"><?= (int) $estadisticas['materias'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">En 6 grandes categorías</p>
                </div>
                <div class="p-4 sm:p-5 rounded-2xl bg-white/5 backdrop-blur border border-white/10">
                    <span class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Bloques Libres</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-indigo-300 mt-1"><?= (int) $estadisticas['horarios'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">Modalidad virtual y presencial</p>
                </div>
                <div class="p-4 sm:p-5 rounded-2xl bg-white/5 backdrop-blur border border-white/10">
                    <span class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Asesorías Impartidas</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-violet-300 mt-1"><?= (int) $estadisticas['completadas'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">Sesiones completadas</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="barra-categorias" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12 relative z-20">
    <div class="bg-indigo-50/95 backdrop-blur-md rounded-[36px] p-3 sm:p-4 shadow-lg border border-indigo-100">
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
            <a
                href="index.php#buscador-mentores"
                data-area-pill=""
                class="pill-categoria group flex flex-col items-center justify-center text-center py-3.5 px-2 rounded-3xl transition-all cursor-pointer <?= $areaSeleccionada === '' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-indigo-200 font-extrabold' : 'text-slate-700 hover:bg-white/70 hover:text-indigo-600 font-semibold' ?>"
            >
                <div class="mb-1.5 transition-transform group-hover:scale-110 <?= $areaSeleccionada === '' ? 'text-indigo-600' : 'text-slate-700 group-hover:text-indigo-600' ?>">
                    <?= icono_categoria_svg('all') ?>
                </div>
                <span class="text-xs sm:text-[13px] leading-tight">Todas</span>
                <span class="text-[10px] text-slate-400 font-medium mt-0.5"><?= (int) $estadisticas['mentores'] ?> mentores</span>
            </a>

            <?php foreach ($categorias as $cat): ?>
                <?php $activa = ($areaSeleccionada === $cat['nombre']); ?>
                <a
                    href="index.php?area=<?= urlencode($cat['nombre']) ?>#buscador-mentores"
                    data-area-pill="<?= e($cat['nombre']) ?>"
                    class="pill-categoria group flex flex-col items-center justify-center text-center py-3.5 px-2 rounded-3xl transition-all cursor-pointer <?= $activa ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-indigo-200 font-extrabold' : 'text-slate-700 hover:bg-white/70 hover:text-indigo-600 font-semibold' ?>"
                >
                    <div class="mb-1.5 transition-transform group-hover:scale-110 <?= $activa ? 'text-indigo-600' : 'text-slate-700 group-hover:text-indigo-600' ?>">
                        <?= icono_categoria_svg($cat['icono']) ?>
                    </div>
                    <span class="text-xs sm:text-[13px] leading-tight"><?= e($cat['nombre']) ?></span>
                    <span class="text-[10px] text-slate-400 font-medium mt-0.5">
                        <?= (int) $cat['total_mentores'] ?> <?= (int) $cat['total_mentores'] === 1 ? 'mentor' : 'mentores' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="buscador-mentores" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-5 relative z-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/90 p-5 sm:p-6">
        <form id="form-filtros" method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

            <div class="md:col-span-4">
                <label for="input-busqueda" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Buscar por materia, tema o nombre
                </label>
                <input
                    type="text"
                    id="input-busqueda"
                    name="q"
                    value="<?= e($busqueda) ?>"
                    placeholder="Ej. Álgebra, Inglés, Física, Biología, Programación..."
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div class="md:col-span-3">
                <label for="select-area" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Categoría
                </label>
                <select
                    id="select-area"
                    name="area"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= e($cat['nombre']) ?>" <?= $areaSeleccionada === $cat['nombre'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="select-nivel" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Nivel educativo
                </label>
                <select
                    id="select-nivel"
                    name="nivel"
                    class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Cualquiera</option>
                    <?php foreach (niveles_habilidad() as $nivelOpcion): ?>
                        <?php if ($nivelOpcion === 'Todos los niveles') { continue; } ?>
                        <option value="<?= e($nivelOpcion) ?>" <?= $nivelSeleccionado === $nivelOpcion ? 'selected' : '' ?>><?= e($nivelOpcion) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-3 flex items-end gap-2">
                <div class="flex-1">
                    <label for="select-modalidad" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Modalidad
                    </label>
                    <select
                        id="select-modalidad"
                        name="modalidad"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Cualquiera</option>
                        <option value="Virtual" <?= $modalidadSeleccionada === 'Virtual' ? 'selected' : '' ?>>Virtual / Online</option>
                        <option value="Presencial" <?= $modalidadSeleccionada === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition cursor-pointer">
                    Filtrar
                </button>
                <?php if ($busqueda !== '' || $materiaSeleccionada !== '' || $modalidadSeleccionada !== '' || $areaSeleccionada !== '' || $nivelSeleccionado !== ''): ?>
                    <a href="index.php" class="px-3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition" title="Limpiar filtros">
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 flex items-center gap-2.5">
                <span id="titulo-categoria-activa">
                    <?= $areaSeleccionada !== '' ? 'Mentores en ' . e($areaSeleccionada) : 'Directorio de Mentores' ?>
                </span>
                <span id="contador-mentores" class="px-2.5 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold">
                    <?= count($mentores) ?> disponibles
                </span>
            </h2>
            <?php if ($areaSeleccionada !== '' || $materiaSeleccionada !== '' || $busqueda !== '' || $modalidadSeleccionada !== '' || $nivelSeleccionado !== ''): ?>
                <p class="text-xs text-slate-500 mt-1">
                    Filtros activos:
                    <?php if ($areaSeleccionada !== ''): ?>
                        <span class="inline-block px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold mr-1">Categoría: <?= e($areaSeleccionada) ?></span>
                    <?php endif; ?>
                    <?php if ($materiaSeleccionada !== ''): ?>
                        <span class="inline-block px-2 py-0.5 rounded bg-slate-200 text-slate-800 font-semibold mr-1">Materia: <?= e($materiaSeleccionada) ?></span>
                    <?php endif; ?>
                    <?php if ($nivelSeleccionado !== ''): ?>
                        <span class="inline-block px-2 py-0.5 rounded bg-slate-200 text-slate-800 font-semibold mr-1">Nivel: <?= e($nivelSeleccionado) ?></span>
                    <?php endif; ?>
                    <?php if ($modalidadSeleccionada !== ''): ?>
                        <span class="inline-block px-2 py-0.5 rounded bg-slate-200 text-slate-800 font-semibold mr-1">Modalidad: <?= e($modalidadSeleccionada) ?></span>
                    <?php endif; ?>
                    <?php if ($busqueda !== ''): ?>
                        <span class="inline-block px-2 py-0.5 rounded bg-slate-200 text-slate-800 font-semibold">"<?= e($busqueda) ?>"</span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($modalidadSeleccionada === 'Presencial'): ?>
        <p class="mb-5 text-xs px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900">
            <?php if (tiene_ciudad($ciudadUsuario)): ?>
                📍 Clases presenciales: solo ves mentores de <strong><?= e($ciudadUsuario) ?></strong>, tu ciudad.
            <?php elseif ($usuario): ?>
                📍 Para ver mentores presenciales de tu ciudad, dinos en cuál estás:
                <form method="POST" action="accion_sesion.php" class="inline-flex gap-2 ml-1 align-middle">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="accion" value="guardar_ciudad">
                    <input type="hidden" name="volver" value="index.php?modalidad=Presencial">
                    <input type="text" name="ubicacion" required minlength="2" maxlength="80" autocomplete="off" placeholder="Tu ciudad" class="px-2 py-1 rounded-lg border border-emerald-300 text-xs bg-white">
                    <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold cursor-pointer">Guardar</button>
                </form>
            <?php else: ?>
                📍 <a href="login.php" class="font-bold underline">Inicia sesión</a> para ver solo los mentores presenciales de tu ciudad.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (empty($mentores)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-10 text-center max-w-xl mx-auto my-6 shadow-2xs">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto font-extrabold text-xl mb-3">
                ?
            </div>
            <h3 class="text-lg font-extrabold text-slate-900">Sin mentores para este criterio</h3>
            <p class="text-sm text-slate-600 mt-1">
                No encontramos coincidencias para <strong>"<?= e($busqueda ?: ($materiaSeleccionada ?: ($areaSeleccionada ?: $nivelSeleccionado))) ?>"</strong>. Intenta con otra categoría o explora el catálogo completo.
            </p>
            <a href="index.php" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition">
                Restablecer catálogo
            </a>
        </div>
    <?php else: ?>
        <div id="grid-mentores" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($mentores as $mentor): ?>
                <?php
                $materiasTexto = implode(' ', array_column($mentor['habilidades'], 'materia'));
                $areasTexto = implode('|', $mentor['areas']);
                $temasTexto = implode(' ', array_column($mentor['habilidades'], 'descripcion_tema'));
                $etiquetaAreas = implode(' · ', $mentor['areas']);
                $partesNombre = explode(' ', trim($mentor['nombre']));
                $iniciales = strtoupper(substr($partesNombre[0] ?? 'M', 0, 1) . substr($partesNombre[1] ?? '', 0, 1));
                ?>
                <article
                    class="tarjeta-mentor group bg-white rounded-3xl border border-slate-200/90 hover:border-indigo-400 shadow-2xs hover:shadow-lg transition-all duration-200 overflow-hidden flex flex-col justify-between"
                    data-search="<?= e(strtolower($mentor['nombre'] . ' ' . $mentor['ocupacion'] . ' ' . $materiasTexto . ' ' . $etiquetaAreas . ' ' . $temasTexto)) ?>"
                    data-areas="<?= e($areasTexto) ?>"
                >
                    <div>
                        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-4 flex items-center justify-between gap-3">
                            <span class="text-[11px] font-bold uppercase tracking-widest text-indigo-300 truncate">
                                <?= e($etiquetaAreas) ?>
                            </span>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2.5 py-0.5 rounded-full bg-white/10 text-amber-300 text-xs font-bold">
                                    <?= $mentor['calificacion_promedio'] === null ? 'Nuevo' : '★ ' . number_format((float) $mentor['calificacion_promedio'], 1) . ' (' . (int) $mentor['total_calificaciones'] . ')' ?>
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-extrabold text-lg flex items-center justify-center shrink-0 shadow-xs">
                                    <?= e($iniciales) ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-lg font-extrabold text-slate-900 group-hover:text-indigo-600 transition leading-snug">
                                            <?= e($mentor['nombre']) ?>
                                        </h3>
                                        <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-lg border border-indigo-100">
                                            <?= e($mentor['experiencia']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-600 mt-0.5">
                                        <?= e(titular_publico($mentor['ocupacion'])) ?>
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3 mt-2 text-[11px] text-slate-500 font-medium">
                                        <span>📍 <?= e($mentor['ubicacion']) ?><?php if (!empty($mentor['en_tu_ciudad'])): ?> <span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">En tu ciudad</span><?php endif; ?></span>
                                        <span>·</span>
                                        <span>Modalidad: <strong class="text-slate-700"><?= e($mentor['modalidad']) ?></strong></span>
                                        <span>·</span>
                                        <span class="font-bold text-emerald-700"><?= e(etiqueta_tarifa((int) $mentor['tarifa_hora'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-4 pl-3.5 border-l-2 border-indigo-200 text-xs text-slate-600 leading-relaxed line-clamp-2">
                                <?= e(trim((string) $mentor['biografia']) !== '' ? $mentor['biografia'] : 'Este mentor aún no agrega una biografía.') ?>
                            </p>

                            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-12 gap-4">
                                <div class="sm:col-span-7">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                                        Materias que imparte
                                    </p>
                                    <div class="space-y-1.5">
                                        <?php foreach ($mentor['habilidades'] as $hab): ?>
                                            <div class="flex items-center justify-between gap-2 px-3 py-1.5 rounded-xl <?= ($areaSeleccionada !== '' && $hab['area'] === $areaSeleccionada) ? 'bg-indigo-50/90 border border-indigo-300' : 'bg-slate-50 border border-slate-200/70' ?>">
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-slate-800 block truncate"><?= e($hab['materia']) ?></span>
                                                    <span class="text-[10px] text-slate-500"><?= e($hab['area']) ?></span>
                                                </div>
                                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-100/80 text-indigo-800 shrink-0">
                                                    <?= e($hab['nivel']) ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="sm:col-span-5">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                                        Próximos espacios (<?= count($mentor['disponibilidades']) ?>)
                                    </p>
                                    <div class="space-y-1.5">
                                        <?php foreach (array_slice($mentor['disponibilidades'], 0, 2) as $disp): ?>
                                            <div class="px-3 py-1.5 rounded-xl bg-teal-50/60 border border-teal-200/70 flex items-center justify-between text-xs">
                                                <span class="font-bold text-teal-950"><?= e($disp['dia_semana']) ?></span>
                                                <span class="text-[11px] font-semibold text-teal-800"><?= e($disp['hora_inicio']) ?>–<?= e($disp['hora_fin']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 bg-slate-50/90 border-t border-slate-200/80 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-500">
                            <?= count($mentor['disponibilidades']) ?> horarios disponibles
                        </span>
                        <?php if ($usuario && (int) $usuario['id'] === (int) $mentor['id']): ?>
                            <a href="panel.php" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-2xs transition">
                                Tu perfil · Editar en Mi Panel →
                            </a>
                        <?php else: ?>
                            <a
                                href="perfil.php?id=<?= (int) $mentor['id'] ?>"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs transition"
                            >
                                Ver perfil y agendar →
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php render_footer(); ?>
