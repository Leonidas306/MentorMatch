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
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white font-extrabold text-base shadow-sm group-hover:scale-105 transition">
                    MM
                </div>
                <div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900 block leading-none">Mentor<span class="text-indigo-600">Match</span></span>
                    <span class="text-[11px] text-slate-500 font-medium">Red de Asesorías y Habilidades</span>
                </div>
            </a>

            <nav class="flex items-center gap-1 sm:gap-2 text-sm font-semibold">
                <a href="index.php" class="px-3.5 py-2 rounded-xl transition <?= $paginaActiva === 'inicio' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    Explorar Mentores
                </a>
                <a href="agenda.php" class="px-3.5 py-2 rounded-xl transition <?= $paginaActiva === 'agenda' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    Agenda y Estados
                </a>
                <?php if ($usuario): ?>
                    <div class="hidden md:flex items-center gap-2.5 pl-3 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-800 leading-none"><?= e($usuario['nombre']) ?></p>
                            <p class="text-[11px] text-indigo-600 font-semibold capitalize">Rol: <?= e($usuario['rol'] === 'ambos' ? 'Mentor y Estudiante' : $usuario['rol']) ?></p>
                        </div>
                        <a href="logout.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-700 border border-slate-200 text-xs font-semibold transition">
                            Salir
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="ml-1 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold shadow-xs transition">
                        Iniciar Sesión
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1">
    <?php
}

/**
 * Muestra el pie de página y carga el archivo JavaScript del cliente
 */
function render_footer(): void
{
    ?>
    </main>

    <footer class="bg-white border-t border-slate-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                <p class="font-bold text-slate-800">MentorMatch · Plataforma Abierta de Asesorías e Intercambio de Habilidades</p>
                <p>Proyecto Final · Aplicaciones de Internet (2026)</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="index.php" class="hover:text-indigo-600 underline">Catálogo</a>
                <span>·</span>
                <a href="agenda.php" class="hover:text-indigo-600 underline">Estados de Sesión</a>
                <span>·</span>
                <a href="login.php" class="hover:text-indigo-600 underline">Acceso Demo</a>
            </div>
        </div>
    </footer>
    <script src="assets/app.js"></script>
</body>
</html>
    <?php
}
