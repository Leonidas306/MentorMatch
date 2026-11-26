<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$modo = ($_GET['modo'] ?? 'login') === 'registro' ? 'registro' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'La sesión del formulario caducó. Por favor intenta de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? 'login';
        if ($accion === 'registro') {
            $modo = 'registro';
            $resultado = registrar_nuevo_usuario($_POST);
            if ($resultado['exito']) {
                // Mentores terminan de configurar su perfil en el panel.
                if (in_array($_SESSION['usuario_rol'] ?? '', ['mentor', 'ambos'], true)) {
                    flash_set('exito', '¡Cuenta creada! Completa tu perfil: agrega tu biografía, tus materias y tus horarios para aparecer en el directorio.');
                    redirigir('panel.php');
                }
                flash_set('exito', '¡Cuenta creada! Ya puedes buscar un mentor y solicitar tu primera asesoría.');
                redirigir('index.php');
            }
            $error = $resultado['error'];
        } else {
            $correo = (string) ($_POST['correo'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $resultado = iniciar_sesion_usuario($correo, $password);
            if ($resultado['exito']) {
                header('Location: index.php');
                exit;
            }
            $error = $resultado['error'];
        }
    }
}

// Valores previos del formulario de registro.
$prev = static fn(string $campo, string $defecto = ''): string => $modo === 'registro' && $_SERVER['REQUEST_METHOD'] === 'POST'
    ? trim((string) ($_POST[$campo] ?? $defecto))
    : $defecto;
$rolPrevio = $prev('rol', 'estudiante');
$modalidadPrevia = $prev('modalidad', 'Ambas');

render_header('Iniciar Sesión y Registro', 'login');
?>

<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex justify-center">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
            <div class="flex items-center gap-2 border-b border-slate-200 pb-4 mb-6">
                <a
                    href="login.php?modo=login"
                    class="px-4 py-2 rounded-xl text-sm font-extrabold transition <?= $modo === 'login' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:text-slate-900' ?>"
                >
                    Iniciar Sesión
                </a>
                <a
                    href="login.php?modo=registro"
                    class="px-4 py-2 rounded-xl text-sm font-extrabold transition <?= $modo === 'registro' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:text-slate-900' ?>"
                >
                    Crear Cuenta
                </a>
            </div>

            <?php if ($error !== ''): ?>
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($modo === 'login'): ?>
                <h1 class="text-2xl font-extrabold text-slate-900">Bienvenido a MentorMatch</h1>
                <p class="text-xs text-slate-500 mt-1 mb-6">
                    Ingresa con tu correo electrónico y tu contraseña para continuar.
                </p>

                <form method="POST" action="login.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="accion" value="login">

                    <div>
                        <label for="login-correo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Correo Electrónico
                        </label>
                        <input
                            type="email"
                            id="login-correo"
                            name="correo"
                            required
                            placeholder="tu.correo@ejemplo.com"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="login-password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Contraseña
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="login-password"
                                name="password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-4 pr-16 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                            <button type="button" data-toggle-password="login-password" class="absolute inset-y-0 right-2 px-2 text-[11px] font-bold text-slate-500 hover:text-indigo-700 cursor-pointer">
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-xs transition cursor-pointer"
                    >
                        Entrar a MentorMatch
                    </button>
                </form>
            <?php else: ?>
                <h1 class="text-2xl font-extrabold text-slate-900">Crea tu Cuenta en MentorMatch</h1>
                <p class="text-xs text-slate-500 mt-1 mb-6">
                    Encuentra un mentor para regularizar tus materias o ofrece asesorías a otros estudiantes.
                </p>

                <form id="form-registro" method="POST" action="login.php?modo=registro" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="accion" value="registro">

                    <!-- Se registra primero el rol -->
                    <fieldset>
                        <legend class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">¿Cómo quieres participar? *</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <?php
                            $opcionesRol = [
                                'estudiante' => ['Quiero tomar clases', 'Estudiante'],
                                'mentor' => ['Quiero dar clases', 'Mentor'],
                                'ambos' => ['Ambas cosas', 'Mentor y Estudiante'],
                            ];
                            foreach ($opcionesRol as $valor => [$titulo, $sub]):
                            ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="rol" value="<?= e($valor) ?>" data-rol-radio class="peer sr-only" <?= $rolPrevio === $valor ? 'checked' : '' ?>>
                                    <span class="block text-center px-3 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-800 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 transition">
                                        <span class="block text-sm font-extrabold"><?= e($titulo) ?></span>
                                        <span class="block text-[11px] font-medium opacity-70"><?= e($sub) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div>
                        <label for="reg-nombre" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nombre Completo *</label>
                        <input type="text" id="reg-nombre" name="nombre" required value="<?= e($prev('nombre')) ?>" placeholder="Tu nombre y apellidos" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-correo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Correo Electrónico *</label>
                            <input type="email" id="reg-correo" name="correo" required value="<?= e($prev('correo')) ?>" placeholder="nombre@correo.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                        <div>
                            <label for="reg-password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Contraseña (mín. 6) *</label>
                            <div class="relative">
                                <input type="password" id="reg-password" name="password" required minlength="6" placeholder="••••••••" class="w-full pl-3.5 pr-16 py-2.5 rounded-xl border border-slate-300 text-sm">
                                <button type="button" data-toggle-password="reg-password" class="absolute inset-y-0 right-2 px-2 text-[11px] font-bold text-slate-500 hover:text-indigo-700 cursor-pointer">
                                    Mostrar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-nivel" id="label-nivel" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Grado que cursas *
                            </label>
                            <select id="reg-nivel" name="nivel_educativo" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                                <option value="">Selecciona…</option>
                                <?php foreach (niveles_usuario() as $nivel): ?>
                                    <option value="<?= e($nivel) ?>" <?= $prev('nivel_educativo') === $nivel ? 'selected' : '' ?>><?= e($nivel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Al elegir "Otro", aparece un campo nuevo para escribir el grado estudiado -->
                            <input
                                type="text"
                                id="reg-nivel-otro"
                                name="nivel_otro"
                                maxlength="40"
                                autocomplete="off"
                                value="<?= e($prev('nivel_otro')) ?>"
                                placeholder="Escribe tu grado (ej. Maestría, Técnico, Idiomas)"
                                class="<?= $prev('nivel_educativo') === 'Otro' ? '' : 'hidden ' ?>mt-2 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm"
                            >
                        </div>

                        <!-- Solo visible para mentores o personas con el rol de ambos -->
                        <div id="bloque-experiencia" class="hidden">
                            <label for="reg-anios" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Años dando clases</label>
                            <input type="number" id="reg-anios" name="anios_experiencia" min="0" max="60" value="<?= e($prev('anios_experiencia', '0')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-modalidad" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Modalidad preferida</label>
                            <select id="reg-modalidad" name="modalidad" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                                <option value="Ambas" <?= $modalidadPrevia === 'Ambas' ? 'selected' : '' ?>>Virtual y presencial</option>
                                <option value="Virtual" <?= $modalidadPrevia === 'Virtual' ? 'selected' : '' ?>>Solo virtual (online)</option>
                                <option value="Presencial" <?= $modalidadPrevia === 'Presencial' ? 'selected' : '' ?>>Solo presencial</option>
                            </select>
                        </div>
                        <!-- Si la modalidad es "Virtual", se oculta (la ciudad solo importa para clases presenciales) -->
                        <div id="bloque-ubicacion">
                            <label for="reg-ubicacion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Ciudad</label>
                            <input type="text" id="reg-ubicacion" name="ubicacion" value="<?= e($prev('ubicacion')) ?>" placeholder="Ej. Ciudad de México" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="reg-telefono" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            WhatsApp o teléfono <span class="normal-case font-medium text-slate-400">(opcional)</span>
                        </label>
                        <input type="tel" id="reg-telefono" name="telefono" value="<?= e($prev('telefono')) ?>" placeholder="Ej. 55 1234 5678" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        <p class="text-[11px] text-slate-400 mt-1">Solo se comparte con la otra persona cuando una sesión es aceptada.</p>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-sm transition cursor-pointer"
                    >
                        Crear Cuenta e Iniciar Sesión
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php render_footer(); ?>
