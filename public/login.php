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
                header('Location: index.php');
                exit;
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

render_header('Iniciar Sesión y Registro', 'login');
?>

<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
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
                    Crear Cuenta Abierta
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
                    Ingresa con tu correo electrónico o selecciona una cuenta de demostración a la derecha con un solo clic.
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
                        <input
                            type="password"
                            id="login-password"
                            name="password"
                            required
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
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
                    Abierto a estudiantes, profesionales, autodidactas y cualquier persona que quiera aprender o compartir una habilidad.
                </p>

                <form method="POST" action="login.php?modo=registro" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="accion" value="registro">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nombre Completo *</label>
                            <input type="text" name="nombre" required placeholder="Tu nombre y apellidos" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Ubicación o Ciudad</label>
                            <input type="text" name="ubicacion" value="Remoto / Online" placeholder="Ej. Ciudad de México / Online" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Correo Electrónico *</label>
                            <input type="email" name="correo" required placeholder="nombre@correo.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Contraseña (mín. 6 caracteres) *</label>
                            <input type="password" name="password" required minlength="6" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Ocupación o Especialidad</label>
                            <input type="text" name="ocupacion" value="Desarrollador / Estudiante / Autodidacta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nivel / Experiencia</label>
                            <input type="text" name="experiencia" value="Intermedio" placeholder="Ej. 3 años / Inicial" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Rol en la Plataforma</label>
                        <select name="rol" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white">
                            <option value="ambos">Ambos perfiles (Mentor y Estudiante/Aprendiz)</option>
                            <option value="estudiante">Solo Estudiante / Aprendiz (Solicitar asesorías)</option>
                            <option value="mentor">Solo Mentor (Impartir asesorías)</option>
                        </select>
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

        <div class="lg:col-span-5 bg-slate-900 text-white rounded-2xl p-6 sm:p-7 border border-slate-800 space-y-4">
            <div class="inline-block px-2.5 py-1 rounded-md bg-amber-400/20 text-amber-300 text-xs font-bold">
                🔑 Acceso Rápido para Demostración
            </div>
            <h2 class="text-lg font-extrabold">Cuentas Semilla Preconfiguradas</h2>
            <p class="text-xs text-slate-300 leading-relaxed">
                Haz clic en cualquiera de los botones para autocompletar el formulario y probar los distintos roles exigidos en la ficha de MentorMatch (contraseña: <code class="text-amber-300 font-bold">demo1234</code>):
            </p>

            <div class="space-y-3 pt-1">
                <div class="p-3.5 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-between gap-2">
                    <div>
                        <p class="text-xs font-extrabold text-amber-300">Alejandro Morales (Rol: Ambos)</p>
                        <p class="text-[11px] text-slate-400">alejandro.morales@mentormatch.demo</p>
                    </div>
                    <button
                        type="button"
                        onclick="autocompletarDemo('alejandro.morales@mentormatch.demo', 'demo1234')"
                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs cursor-pointer"
                    >
                        Usar cuenta
                    </button>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-between gap-2">
                    <div>
                        <p class="text-xs font-extrabold text-emerald-300">Valeria Ríos (Rol: Ambos)</p>
                        <p class="text-[11px] text-slate-400">valeria.rios@mentormatch.demo</p>
                    </div>
                    <button
                        type="button"
                        onclick="autocompletarDemo('valeria.rios@mentormatch.demo', 'demo1234')"
                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs cursor-pointer"
                    >
                        Usar cuenta
                    </button>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-between gap-2">
                    <div>
                        <p class="text-xs font-extrabold text-sky-300">Luis Fernando Gómez (Rol: Estudiante)</p>
                        <p class="text-[11px] text-slate-400">luis.gomez@mentormatch.demo</p>
                    </div>
                    <button
                        type="button"
                        onclick="autocompletarDemo('luis.gomez@mentormatch.demo', 'demo1234')"
                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs cursor-pointer"
                    >
                        Usar cuenta
                    </button>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 text-[11px] text-slate-400 space-y-1">
                <p>🔒 <strong>Seguridad implementada:</strong></p>
                <p>• Hash con <code class="text-slate-200">password_hash()</code> y <code class="text-slate-200">password_verify()</code>.</p>
                <p>• Consultas preparadas PDO y token CSRF en formularios.</p>
            </div>
        </div>
    </div>
</section>

<?php render_footer(); ?>
