<?php
declare(strict_types=1);

/**
 * Código HTML5, configuración de Tailwind CSS, fuente Plus Jakarta Sans y la barra de navegación
 */
function render_header(string $tituloPagina, string $paginaActiva = 'inicio'): void
{
    $usuario = usuario_actual();
    ?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> · MentorMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans min-h-screen flex flex-col selection:bg-indigo-200 selection:text-indigo-950">
    <!-- Barra de navegación principal -->
    <header class="bg-white/95 backdrop-blur sticky top-0 z-40 border-b border-slate-200/80 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center
                 text-white font-extrabold text-base shadow-sm group-hover:scale-105 transition">
                    MM
                </div>
                <div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900 block leading-none">Mentor<span 
                    class="text-indigo-600">Match</span></span>
                    <span class="text-[11px] text-slate-500 font-medium">Asesorías de regularización</span>
                </div>
            </a>

            <nav class="flex items-center gap-1 sm:gap-2 text-sm font-semibold">
                <a href="index.php" class="px-3.5 py-2 rounded-xl transition <?= 
                $paginaActiva === 'inicio' ? 'bg-indigo-50 text-indigo-700 font-bold' : 
                'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    Explorar Mentores
                </a>
                <?php if ($usuario): ?>
                    <?php if (in_array($usuario['rol'], ['mentor', 'ambos'], true)): ?>
                        <a href="panel.php" class="px-3.5 py-2 rounded-xl transition 
                        <?= $paginaActiva === 'panel' ? 'bg-indigo-50 text-indigo-700 font-bold' : 
                        'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Mi Panel
                        </a>
                    <?php endif; ?>
                    <a href="agenda.php" class="px-3.5 py-2 rounded-xl transition 
                    <?= $paginaActiva === 'agenda' ? 'bg-indigo-50 text-indigo-700 font-bold' : 
                    'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        Mi Agenda
                    </a>
                <?php endif; ?>
                <?php if ($usuario): ?>
                    <div class="hidden md:flex items-center gap-2.5 pl-3 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-800 leading-none"><?= 
                            e($usuario['nombre']) ?></p>
                            <p class="text-[11px] text-indigo-600 font-semibold capitalize">Rol: <?= 
                            e($usuario['rol'] === 'ambos' ? 'Mentor y Estudiante' : $usuario['rol']) ?></p>
                        </div>
                        <a href="logout.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 
                        text-slate-700 hover:text-rose-700 border border-slate-200 text-xs font-semibold transition">
                            Salir
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="ml-1 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white 
                    text-xs sm:text-sm font-bold shadow-xs transition">
                        Iniciar Sesión
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1">
    <?php
    // Aviso de "éxito" / "error" tras una acción.
    $flash = flash_get();
    if ($flash !== null):
        $esExito = $flash['tipo'] === 'exito';
        ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
            <div role="status" class="p-3.5 rounded-xl border text-sm font-semibold <?= $esExito ? 
            'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 
            text-rose-800' ?>">
                <?= $esExito ? '✓' : '⚠️' ?> <?= e($flash['mensaje']) ?>
            </div>
        </div>
    <?php endif;
}

/**
 * Muestra el pie de página y carga el archivo JavaScript del cliente
 */
function render_footer(): void
{
    ?>
    </main>

    <footer class="bg-white border-t border-slate-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col 
        sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                <p class="font-bold text-slate-800">MentorMatch · Plataforma de Asesorías de Regularización</p>
                <p>Proyecto Final · Aplicaciones de Internet (2026)</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="index.php" class="hover:text-indigo-600 underline">Catálogo</a>
                <span>·</span>
                <a href="agenda.php" class="hover:text-indigo-600 underline">Mi Agenda</a>
            </div>
        </div>
    </footer>
    <script src="assets/app.js"></script>
</body>
</html>
    <?php
}

/**
 * Boton para realizar una accion sobre una solicitud mediante POST y CSRF.
 * $volver: pagina a la que regresa despues de realizar la accion.
 */
function boton_accion_sesion(string $accion, int $solicitudId, string $texto, string $clases, string $volver, string $confirmar = ''): string
{
    $onsubmit = $confirmar !== '' ? ' onsubmit="return confirm(\'' . e($confirmar) . '\');"' : '';
    return '<form method="POST" action="accion_sesion.php" class="inline"' . $onsubmit . '>'
        . '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="accion" value="' . e($accion) . '">'
        . '<input type="hidden" name="solicitud_id" value="' . $solicitudId . '">'
        . '<input type="hidden" name="volver" value="' . e($volver) . '">'
        . '<button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer ' . $clases . '">' . e($texto) . '</button>'
        . '</form>';
}

/* ==========================================================================
 * Componentes de reseñas: estrellas, selector, resumen y lista
 * ========================================================================== */

/** Convierte una fecha con formato "2026-11-13" a "13 nov 2026". */
function fecha_corta(string $fecha): string
{
    $meses = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 
    7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
    $t = strtotime($fecha);
    return $t ? date('j', $t) . ' ' . $meses[(int) date('n', $t)] . ' ' . date('Y', $t) : $fecha;
}

/** Muestra 5 estrellas en total y rellena las que correspondan segun el valor de $valor. */
function render_estrellas(?float $valor, string $tamano = 'text-sm'): string
{
    $llenas = (int) round((float) $valor);
    $html = '<span class="inline-flex ' . e($tamano) . ' leading-none" role="img" 
    aria-label="' . ($valor === null ? 'Sin calificación' : e(number_format($valor, 1)) . 
    ' de 5 estrellas') . '">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $llenas ? 'text-amber-400' : 'text-slate-300') . '">★</span>';
    }
    return $html . '</span>';
}

/**
 * Selector de 1 a 5 estrellas.
 */
function render_selector_estrellas(string $idBase): string
{
    $html = '<div class="flex items-center gap-3 flex-wrap">';
    $html .= '<div class="flex flex-row-reverse justify-end gap-0.5" role="radiogroup" 
    aria-label="Calificación en estrellas">';
    for ($i = 5; $i >= 1; $i--) {
        $id = e($idBase) . '-' . $i;
        $html .= '<input type="radio" name="calificacion" id="' . $id . '" value="' . $i . '" class="peer sr-only">'
            . '<label for="' . $id . '" title="' . $i . ' de 5" class="cursor-pointer 
            text-3xl leading-none text-slate-300 hover:text-amber-400 peer-checked:text-amber-400 
            peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 rounded">★</label>';
    }
    $html .= '</div>';
    $html .= '<input type="radio" name="calificacion" id="' . e($idBase) . '-0" 
    value="" class="sr-only" checked>'
        . '<label for="' . e($idBase) . '-0" class="text-[11px] font-semibold 
        text-slate-400 hover:text-slate-700 underline cursor-pointer">Quitar estrellas</label>';
    return $html . '</div>';
}

/** Muestra el promedio de calificaciones y las barras con la cantidad de estrellas. */
function render_resumen_resenas(array $resumen): void
{
    $total = max(1, (int) $resumen['total_calificaciones']);
    ?>
    <div class="flex flex-wrap items-center gap-6">
        <div class="text-center">
            <p class="text-4xl font-extrabold text-slate-900"><?= $resumen['promedio']
             === null ? '—' : e(number_format((float) $resumen['promedio'], 1)) ?></p>
            <?= render_estrellas($resumen['promedio'], 'text-lg') ?>
            <p class="text-[11px] text-slate-500 mt-1">
                <?= (int) $resumen['total_calificaciones'] ?> <?= (int) 
                $resumen['total_calificaciones'] === 1 ? 'calificación' : 'calificaciones' ?>
                · <?= (int) $resumen['total_comentarios'] ?> <?= (int) 
                $resumen['total_comentarios'] === 1 ? 'comentario' : 'comentarios' ?>
            </p>
        </div>
        <div class="flex-1 min-w-[180px] space-y-1">
            <?php foreach ($resumen['distribucion'] as $estrellas => $cantidad): ?>
                <div class="flex items-center gap-2 text-[11px] text-slate-600">
                    <span class="w-6 text-right font-semibold"><?= (int) $estrellas 
                    ?> ★</span>
                    <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full bg-amber-400 rounded-full" style="width: 
                        <?= (int) round($cantidad / $total * 100) ?>%"></div>
                    </div>
                    <span class="w-5 text-slate-400"><?= (int) $cantidad ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/** Lista de comentarios, cada uno indica que viene de una sesión completada (verificado previamente). */
function render_lista_resenas(array $resenas): void
{
    ?>
    <ul class="space-y-3">
        <?php foreach ($resenas as $resena): ?>
            <li class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-800 
                        text-xs font-extrabold flex items-center justify-center"><?= 
                        e(recortar_texto((string) $resena['estudiante_publico'], 1)) ?></span>
                        <div>
                            <p class="text-sm font-extrabold text-slate-900 leading-tight">
                                <?= e($resena['estudiante_publico']) ?></p>
                            <p class="text-[11px] text-slate-500 leading-tight"><?= 
                            e($resena['materia']) ?> · <?= e(fecha_corta((string) $resena['fecha_sesion'])) ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if ($resena['calificacion'] !== null): ?>
                            <?= render_estrellas((float) $resena['calificacion']) ?>
                        <?php endif; ?>
                        <p class="text-[10px] font-bold text-emerald-700">✓ Sesión verificada</p>
                    </div>
                </div>
                <?php if (trim((string) $resena['comentario']) !== ''): ?>
                    <p class="text-sm text-slate-700 leading-relaxed mt-2.5"><?= e($resena['comentario']) ?></p>
                <?php else: ?>
                    <p class="text-xs text-slate-400 italic mt-2">Solo dejó calificación, sin comentario.</p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
