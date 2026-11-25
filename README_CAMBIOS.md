# MentorMatch · Registro de cambios (reorientación a regularización escolar)

Fecha: 24 de noviembre de 2026 · **Actualización 2 (PAQ-10): reseñas y calificaciones** — ver sección 2-bis

**Objetivo del cambio:** MentorMatch pasó de ser una "red abierta de intercambio de habilidades" a una **plataforma de clases de regularización para estudiantes**, donde se registran tanto **mentores** como **estudiantes**. Además, el flujo de solicitudes ya **funciona de verdad** (antes la solicitud solo se simulaba y no se guardaba) y el mentor ahora tiene un **panel propio**.

Este archivo está dividido en **paquetes de trabajo (PAQ-1 … PAQ-9)** para repartirlos entre el equipo. Cada paquete indica qué archivos toca, qué cambió y cómo probarlo.

---

## 0. Antes de empezar

1. Reemplaza/actualiza el código con esta versión.
2. **La base de datos cambió de esquema.** Si ya tenías `storage/mentormatch.sqlite`, al abrir la app se **respalda sola** como `storage/mentormatch.old-FECHA.sqlite` y se crea una nueva con los datos semilla. (Si el respaldo falla, por ejemplo en Windows con el servidor abierto, cierra `php -S`, borra `storage/mentormatch.sqlite` y vuelve a abrir.)
3. Levantar: `php -S 127.0.0.1:8000 -t public`
4. Cuentas demo (contraseña `demo1234`):

| Correo | Rol | Para probar |
| :--- | :--- | :--- |
| `alejandro.morales@mentormatch.demo` | Ambos | Panel de mentor, aceptar/rechazar, **completar** (tiene una sesión aceptada del 23-nov ya vencida) |
| `valeria.rios@mentormatch.demo` | Ambos | Tiene una solicitud **pendiente** de Luis |
| `diego.vazquez@mentormatch.demo` | Solo mentor | Verificar que un mentor "solo mentor" no puede solicitar clases |
| `luis.gomez@mentormatch.demo` | Solo estudiante | Solicitar, cancelar, ver su agenda |

> **Orden recomendado de integración:** PAQ-1 → PAQ-2 → (PAQ-3, PAQ-4, PAQ-5, PAQ-6, PAQ-7, PAQ-8 en paralelo) → PAQ-9.
> Los paquetes 3-8 dependen de las funciones nuevas de PAQ-2, así que PAQ-1 y PAQ-2 deben ir primero.

---

## 1. Resumen de paquetes

| Paquete | Qué hace | Archivos | Módulo original de |
| :--- | :--- | :--- | :--- |
| **PAQ-1** | Esquema y datos semilla de regularización + migración automática | `database/schema.sql`, `database/seed.sql`, `src/bootstrap.php` | Jose Pepe / Leonardo |
| **PAQ-2** | Repositorio: categorías escolares, filtro por nivel, **flujo real de estados**, funciones del panel | `src/repository.php` | Jose Pepe |
| **PAQ-3** | Formulario de registro nuevo (rol primero, nivel educativo, sin "ocupación") | `public/login.php`, `src/auth.php`, `public/assets/app.js` (sección PAQ-3) | David Aram |
| **PAQ-4** | Portada y catálogo: textos, categorías, filtro por nivel, limpieza de tarjetas | `public/index.php`, `public/assets/app.js` (buscador) | Cutberto |
| **PAQ-5** | Perfil del mentor + **solicitud real** que se guarda | `public/perfil.php`, `public/assets/app.js` (sección PAQ-5) | Yuliana |
| **PAQ-6** | Agenda personal con botones de acción y pestañas | `public/agenda.php`, `public/accion_sesion.php` (nuevo) | Yuliana |
| **PAQ-7** | **Panel del mentor** (pantalla nueva) | `public/panel.php` (nuevo) | Nuevo |
| **PAQ-8** | Navegación, mensajes de aviso (flash) y pie de página | `src/layout.php` | David Aram |
| **PAQ-9** | Documentación: README y `docs/` actualizados en la Revisión 2 | `README.md`, `docs/HISTORIAS_Y_MODELO.md` | Leonardo |
| **PAQ-10** | **Reseñas y calificación por estrellas** (opcionales) + comentarios verificados en el perfil del mentor | `schema.sql`, `seed.sql`, `bootstrap.php`, `repository.php`, `layout.php`, `agenda.php`, `accion_sesion.php`, `perfil.php`, `panel.php`, `index.php` | Varios (ver 2-bis) |
| **PAQ-11** | **Ronda 3**: campo "Otro" en el registro, filtro por categoría, ajustes de panel/agenda/tarjetas, clases presenciales por ciudad y logística sin choques de horario | ver sección 2-ter | Varios |

---

## 2. Detalle por paquete

### PAQ-1 · Base de datos y bootstrap

**`database/schema.sql`**
- `usuarios`: se agregan `nivel_educativo`, `telefono` y `tarifa_hora` (0 = gratis).
- ~~`usuarios.calificacion_promedio`~~: en la primera entrega pasó a poder ser `NULL`; **en PAQ-10 la columna se eliminó** y la calificación se calcula desde las reseñas (se muestra **"Nuevo"** si nadie ha calificado).
- `usuarios.ocupacion` se conserva pero ahora es el **titular del perfil** (ej. "Ing. en Sistemas & Tutor de Matemáticas"). Ya no se pide en el registro.
- `habilidades.nivel` **cambió de significado**: antes era el dominio del mentor (`Intermedio/Avanzado/Especialista`); ahora es el **nivel educativo al que da la clase** (`Primaria/Secundaria/Preparatoria/Universidad/Todos los niveles`).

**`database/seed.sql`**
- Los 6 mentores y las 9 materias se reescribieron con materias escolares (Matemáticas, Física, Biología, Español, Inglés, Programación). Los IDs 1-9 se conservaron para que `disponibilidades` y `solicitudes` sigan coincidiendo.
- Las 6 categorías nuevas: **Matemáticas, Física y Química, Biología, Español y Humanidades, Inglés e Idiomas, Programación y Tecnología** (reemplazan a Ciencia, Matemáticas, Tecnología y Código, Diseño y Creatividad, Negocios y Finanzas, Idiomas).

**`src/bootstrap.php`**
- `esquema_bd_desactualizado()`: detecta una BD del esquema viejo y la respalda automáticamente (ver sección 0).
- Funciones nuevas: `flash_set()`, `flash_get()` (avisos de una sola vez), `redirigir()`, `requerir_login()`.

**Cómo probar:** abrir la app con una BD nueva; en la portada deben verse 6 mentores y las 6 categorías nuevas.

---

### PAQ-2 · Repositorio (`src/repository.php`)

Es el archivo más grande; **conviene que lo haga una sola persona** para evitar conflictos de merge.

**Catálogos y utilidades nuevas:** `dias_semana()`, `niveles_usuario()`, `niveles_habilidad()`, `definir_categorias()`, `lista_areas()`, `etiqueta_tarifa()`, `formatear_experiencia()`, `normalizar_telefono()`, `nombre_dia_es()`, `proxima_fecha_para_dia()`, `longitud_texto()` y `recortar_texto()` (usan `mbstring` solo si está habilitada, porque en Windows suele venir apagada).

**Catálogo / búsqueda**
- `buscar_mentores()` recibe un nuevo parámetro `$nivel`.
- Solo se listan mentores **visibles**: con al menos 1 materia **y** 1 horario activo (antes aparecían mentores sin horarios, y el contador "7 mentores" no coincidía con las "6 disponibles" del directorio).
- Los mentores sin calificación ("Nuevo") se ordenan después de los calificados.
- `obtener_disponibilidades_por_mentor()` ahora ordena por día de la semana y hora.
- `obtener_mentor_por_id()` **ya no devuelve el correo** (ver sección 3).
- `obtener_estadisticas_sistema()` agrega `completadas`.

**Flujo real de estados (nuevo)** — `pendiente → aceptada → completada`, o `rechazada` / `cancelada`
- `crear_solicitud()`: guarda la solicitud. Valida: no autosolicitud, el solicitante no es "solo mentor", fecha válida y no pasada, la materia y el horario son de ese mentor, **la fecha cae en el día de la semana del bloque**, no es un horario que ya pasó hoy, no hay conflicto con sesiones aceptadas y no hay duplicado activo.
- `responder_solicitud()`: el mentor **acepta** (transacción: revisa conflicto, cambia estado, crea el registro en `sesiones` y **rechaza automáticamente** otras pendientes que choquen) o **rechaza**.
- `cancelar_solicitud()`: el estudiante cancela pendientes; cualquiera de los dos cancela aceptadas (si la fecha no ha pasado). El horario queda libre.
- `completar_sesion()`: el mentor marca como completada una sesión que ya inició, con notas de cierre.
- `obtener_solicitudes_agenda($usuarioId)`: ahora **requiere** el id de usuario (se quitó el modo "todas las de demostración") y agrega la columna calculada `estado_efectivo` (`completada` cuando la sesión se completó).
- `tiene_conflicto_horario()` **cambió de firma**: ahora `(mentorId, fecha, horaInicio, horaFin, excluirSolicitudId = 0)` y detecta **traslapes de rango**, no solo hora de inicio idéntica.

**Funciones del panel del mentor:** `usuario_puede_ser_mentor()`, `actualizar_perfil_mentor()`, `crear_habilidad()`, `eliminar_habilidad()`, `crear_disponibilidad()`, `eliminar_disponibilidad()`.

**Cómo probar:** se prueba a través de PAQ-5, 6 y 7 (no tiene pantalla propia). Las funciones de reseñas se describen en PAQ-10.

---

### PAQ-3 · Registro (`public/login.php`, `src/auth.php`, `app.js`)

- **El rol se elige primero**, con tres opciones: *Quiero tomar clases* / *Quiero dar clases* / *Ambas cosas*.
- **Se quitó** "Ocupación o especialidad" y "Nivel / Experiencia".
- **Se agregó:** Nivel educativo (la etiqueta cambia: "Grado que cursas" para estudiante, "Nivel máximo que enseñas" para mentor), **Años dando clases** (solo aparece para mentor/ambos), **Modalidad** (la **Ciudad** se oculta si elige solo virtual), **WhatsApp/teléfono** opcional y botón **Mostrar/Ocultar contraseña** (también en el login).
- La pestaña "Crear Cuenta Abierta" ahora es **"Crear Cuenta"**.
- Si hay un error, el formulario **conserva lo que escribió** la persona.
- Al registrarse, un **mentor va directo a `panel.php`** para completar su perfil; un estudiante va a la portada. La biografía nace vacía (antes llevaba un texto genérico que hacía que todos aparecieran con "✓ Perfil Completo").
- `registrar_nuevo_usuario()` valida todo en el servidor (el JavaScript solo es comodidad).

**Cómo probar:** `login.php?modo=registro`; cambiar el rol y ver cómo cambian los campos; registrar con un correo repetido y con un nivel inválido.

---

### PAQ-4 · Portada y catálogo (`public/index.php`, `app.js`)

- Textos de la portada orientados a regularización (título, descripción y botones). Se **quitó la insignia** "🎓 Asesorías de regularización entre estudiantes y mentores" del inicio.
- La tarjeta **"Perfil Dual: Mentor / Aprendiz"** se reemplazó por **"Asesorías Impartidas"** (sesiones completadas).
- Los botones de la portada cambian según el usuario (mentor → "Ir a Mi Panel", estudiante → "Ver Mi Agenda", visitante → "Crear mi cuenta").
- Nuevo **filtro "Nivel educativo"** (Primaria, Secundaria, Preparatoria, Universidad) que también incluye a quienes enseñan "Todos los niveles".
- Nuevos íconos SVG `leaf` (Biología) y `book` (Español y Humanidades).
- **Tarjetas de mentor:** se quitó la línea "Rol: Mentor y Aprendiz"; se agregó el **precio** ("Gratis" o "$150 / hr"); la calificación muestra **"Nuevo"** si no hay reseñas; el texto "horarios activos esta semana" ahora dice "horarios disponibles"; el botón es "Ver perfil y agendar →" (y "Tu perfil · Editar en Mi Panel" en la tarjeta del propio mentor).

**Cómo probar:** `index.php?nivel=Secundaria` debe mostrar solo a Mariana Soto y Carlos Mendoza.

---

### PAQ-5 · Perfil y solicitud real (`public/perfil.php`, `app.js`)

- La solicitud **ya se guarda en la base de datos** y redirige a la agenda con un aviso de éxito. Se quitó la "Solicitud Simulada", el folio `SIM-…` y las etiquetas "Revisión 1".
- Se quitó el campo "Nombre de quien solicita": se usa el usuario con sesión iniciada.
- Si no hay sesión, el formulario se reemplaza por botones de *Iniciar sesión / Crear cuenta*. Si es el propio perfil del mentor, aparece un enlace al panel. Si la cuenta es **solo mentor**, aparece un aviso.
- **Se oculta el correo del mentor** en el perfil público (se muestran ciudad, modalidad y precio).
- Al elegir un bloque horario, **la fecha salta sola al próximo día que coincide** (JavaScript en `app.js`, sección PAQ-5).
- Los textos de materias muestran el nivel educativo ("Nivel: Preparatoria").

**Cómo probar:** con `luis.gomez@…` abrir `perfil.php?id=1`, enviar una solicitud con fecha de un día distinto al del bloque (debe rechazarla) y luego con la fecha correcta (debe aparecer en Mi Agenda como PENDIENTE).

---

### PAQ-6 · Agenda y acciones (`public/agenda.php`, `public/accion_sesion.php`)

**Se quitó** de la agenda: "Requisito Revisión 1 · Modelo de Estados", "Datos cargados desde SQLite", el interruptor "Todas las de demostración" y las 4 tarjetas grandes de explicación (ahora son una **leyenda de una línea** con la explicación plegada en "¿Cómo funcionan los estados?").

**Se agregó:**
- La agenda **exige iniciar sesión** y muestra solo las solicitudes del usuario.
- **Pestañas por estado** con contador: Pendientes / Próximas sesiones / Historial / Todas.
- Columna **"Con"** (la otra persona; para el mentor es el estudiante y viceversa). Muestra el teléfono de la otra persona **solo cuando la sesión está aceptada**.
- **Botones de acción** según rol y estado: mentor → *Aceptar / Rechazar / Marcar completada (con notas de cierre)*; estudiante → *Cancelar solicitud*; ambos → *Cancelar* en sesiones aceptadas.
- Estado vacío útil con botones a seguir.
- `accion_sesion.php` (nuevo) recibe los botones por `POST`, valida el CSRF y solo permite regresar a `agenda.php` o `panel.php` (evita redirecciones abiertas). La lógica de "quién puede qué" vive en `repository.php`.

**Cómo probar:** con `valeria.rios@…` aceptar la solicitud pendiente de Luis; con `alejandro.morales@…` marcar como completada la sesión del 23-nov y ver las notas en Historial.

---

### PAQ-7 · Panel del mentor (`public/panel.php`, nuevo)

Solo para cuentas `mentor` o `ambos`. Contiene:
1. **Checklist de perfil completo** (biografía + 1 materia + 1 horario) y contadores (pendientes / próximas / completadas).
2. **Solicitudes pendientes** con *Aceptar / Rechazar*.
3. **Mis horarios:** agregar (día, inicio, fin, modalidad, lugar) y eliminar. Valida que no se traslape con otro horario del mismo día y que un bloque presencial lleve lugar. Eliminar es una **baja lógica** (`activo = 0`) para no perder el vínculo con solicitudes ya hechas.
4. **Mis materias:** agregar (nombre, categoría, nivel educativo, temas) y eliminar. **No deja eliminar** una materia que ya tiene solicitudes (el `ON DELETE CASCADE` borraría también el historial).
5. **Mi perfil público:** titular, años dando clases, tarifa por hora, biografía, modalidad, ciudad y teléfono.

**Cómo probar:** registrar un mentor nuevo → no aparece en el directorio hasta que agregue biografía, una materia y un horario.

---

### PAQ-8 · Navegación y layout (`src/layout.php`)

- Enlace **"Mi Panel"** (solo mentor/ambos) y **"Mi Agenda"** (solo con sesión); antes "Agenda y Estados" era visible para todos.
- **Avisos flash** (verde/rojo) bajo la barra superior, que usan todas las pantallas.
- Textos del encabezado y pie de página actualizados ("Asesorías de regularización").
- Se **quitó el enlace "Acceso Demo"** del pie de página, el mensaje "usa una cuenta demo" del login (`auth.php`) y la función `autocompletarDemo()` de `app.js`. Las cuentas demo siguen existiendo en el seed solo para pruebas del equipo.
- Helper `boton_accion_sesion()` compartido por agenda y panel.

---

## 2-bis. PAQ-10 · Reseñas y calificación por estrellas (actualización 2)

**Qué hace:** cuando el mentor marca una sesión como **completada**, el estudiante puede dejar una **reseña opcional** (estrellas, comentario o ambos). Las reseñas se publican como **comentarios en el perfil del mentor** y de ellas se calcula su **puntaje**. Como solo se puede reseñar una sesión completada y solo la escribe el estudiante de esa sesión, cada comentario sale marcado como **"✓ Sesión verificada"**: eso es lo que le da validez al puntaje.

**Reglas**
- Una reseña **por sesión**, y solo el **estudiante de esa sesión** (no el mentor, no otro estudiante).
- Solo si la sesión está **completada** (no pendiente, aceptada ni cancelada).
- **Todo es opcional:** puede dejar solo estrellas, solo comentario, o ambos; no puede enviar una reseña totalmente vacía (para omitirla simplemente no la envía). Comentario de máximo 500 caracteres.
- **No se edita ni se borra** una vez publicada (el formulario lo avisa).
- Se muestra el nombre **abreviado** del estudiante ("Luis G."), no el completo.

**Archivos y cambios (para repartir)**

| Parte | Archivos | Qué cambió | Persona sugerida |
| :--- | :--- | :--- | :--- |
| **10A · Datos** | `database/schema.sql`, `database/seed.sql`, `src/bootstrap.php` | Tabla nueva `resenas` (`UNIQUE(sesion_id)`, `calificacion` 1-5 opcional, `comentario`, y un `CHECK` que exige estrellas o comentario). Se **eliminó** `usuarios.calificacion_promedio`. El seed ahora trae 9 sesiones completadas más y 10 reseñas de demostración (con y sin estrellas, con y sin comentario). `bootstrap.php` crea la tabla `resenas` **sin borrar datos** si tu BD ya existía. | Jose Pepe |
| **10B · Lógica** | `src/repository.php` | `crear_resena()` (todas las validaciones), `obtener_resenas_mentor()`, `obtener_resumen_resenas()` (promedio, totales y distribución por estrellas), `nombre_publico()`. El promedio ahora se **calcula** con la constante `SQL_CALIFICACION_MENTOR` (`calificacion_promedio`, `total_calificaciones`, `total_resenas`) en `buscar_mentores()` y `obtener_mentor_por_id()`. `obtener_solicitudes_agenda()` agrega `resena_id`, `resena_calificacion` y `resena_comentario`. | Jose Pepe |
| **10C · Componentes** | `src/layout.php` | `render_estrellas()`, `render_selector_estrellas()` (radios con CSS de Tailwind, funciona sin JavaScript), `render_resumen_resenas()` (promedio grande + barras por estrella), `render_lista_resenas()` y `fecha_corta()`. | David Aram |
| **10D · Estudiante** | `public/agenda.php`, `public/accion_sesion.php` | En **Historial**, las sesiones completadas muestran **"Dejar reseña (opcional)"** con estrellas y comentario. Aviso amarillo "Tienes N sesiones completadas sin reseña". Después de reseñar se ve **"Tu reseña"** (y el mentor ve "Reseña del estudiante"). Nueva acción `resenar` en `accion_sesion.php`. | Yuliana |
| **10E · Mentor** | `public/perfil.php`, `public/panel.php`, `public/index.php` | Perfil público: sección **"Reseñas de estudiantes"** (resumen + comentarios, las 20 más recientes) y enlace "N reseñas" bajo el puntaje. Panel: tarjeta **Calificación** y bloque **"Reseñas que he recibido"**. Catálogo: el puntaje ahora es `★ 4.7 (3)` (promedio y número de calificaciones). | Cutberto |

**Cómo probar**
1. Con `alejandro.morales@…` abrir **Mi Agenda** y marcar como completada la sesión del 23-nov (de Luis).
2. Con `luis.gomez@…` abrir **Mi Agenda → Historial**: aparece el aviso y el botón **Dejar reseña (opcional)**. Elegir estrellas y/o escribir un comentario.
3. Abrir `perfil.php?id=1`: el comentario aparece con **"✓ Sesión verificada"** y el promedio sube de ★4.7 (3) a ★4.8 (4).
4. Con Alejandro, en **Mi Panel**, ver el bloque "Reseñas que he recibido".
5. Para ver reseñas de demostración sin hacer nada: `perfil.php?id=1` (una solo con estrellas) y `perfil.php?id=3` (una solo con comentario).

**Si ya tenías una BD de la entrega anterior:** al abrir la app se crea la tabla `resenas` sola, pero **no** se cargan las reseñas de demostración (los mentores aparecerán como "Nuevo" hasta que haya reseñas reales). Para ver los datos de demostración, borra `storage/mentormatch.sqlite`. La columna vieja `calificacion_promedio` queda sin usar en esas BD y no estorba.

---

## 2-ter. PAQ-11 · Ronda 3 (9 cambios pedidos)

| # | Cambio | Dónde |
| :-- | :--- | :--- |
| 1 | **Registro:** al elegir "Otro" en *Grado que cursas* aparece una barra para escribir el grado (obligatoria si elige "Otro"). Se guarda en la columna nueva `usuarios.nivel_otro` y se muestra ese texto en vez de "Otro". | `login.php`, `app.js`, `auth.php`, `schema.sql`, `bootstrap.php`, `repository.php` (`etiqueta_nivel`) |
| 2 | **Filtros activos:** se quitó la palabra "Texto:"; solo se muestra la búsqueda entre comillas. | `index.php` |
| 3 | **Mi Agenda:** el botón "+ Buscar un mentor" ya no aparece para el rol **mentor** (sigue para estudiante y "ambos"). | `agenda.php` |
| 4 | **Mi Panel:** se quitó el autocompletado del titular ("Mentor de regularización"; ahora nace vacío y se escribe a mano), se quitó "0 = gratis" y se agregó una advertencia: *si dejas la tarifa en blanco, tus asesorías quedarán asignadas como gratuitas*. La tarifa también nace vacía cuando es 0. | `panel.php`, `auth.php`, `schema.sql` |
| 5 | **Filtro de materias → filtro por categoría:** el selector largo de materias se cambió por un selector de las 6 categorías ya existentes (sincronizado con los botones de categorías). | `index.php`, `app.js` |
| 6 | **Clases presenciales por ciudad:** un estudiante solo puede agendar un bloque *Presencial* con un mentor de **su misma ciudad** (se ignoran mayúsculas, acentos y el sufijo "/ Online"). En el catálogo, con el filtro *Presencial* y sesión iniciada solo se ven mentores de su ciudad y aparece la etiqueta "En tu ciudad". Si el estudiante no tiene ciudad, puede guardarla desde el catálogo o desde el perfil del mentor. Los bloques presenciales de otra ciudad salen deshabilitados. | `repository.php` (`crear_solicitud`, `buscar_mentores`, `misma_ciudad`, `actualizar_ciudad_usuario`), `perfil.php`, `index.php`, `accion_sesion.php` |
| 7 | **Tarjetas del directorio:** se quitó la etiqueta "✓ Perfil Completo". | `index.php` |
| 8 | **Mi perfil público:** se quitó el autocompletado (`autocomplete="off"` y titular vacío) y *Modalidad* ahora se llama **Preferencia**. | `panel.php` |
| 9 | **Logística de horarios:** ya no se pueden aceptar dos sesiones al mismo tiempo. La revisión ahora cubre a **ambos participantes y ambos papeles** (alguien con rol "ambos" tampoco puede dar y recibir clase a la misma hora). Al aceptar una sesión se rechazan solas las solicitudes pendientes que choquen para el mentor **o** para el estudiante; además no se puede *pedir* un horario que ya choca con una sesión aceptada. | `repository.php` (`tiene_conflicto_horario`, `crear_solicitud`, `responder_solicitud`) |

**Notas para el equipo**
- La base de datos existente **no se borra**: `bootstrap.php` agrega `nivel_otro` con `ALTER TABLE` al abrir la app.
- Los mentores que se registraron antes con el titular "Mentor de regularización" lo verán vacío en su panel (deben escribir el suyo). Mientras tanto, en público se muestra "Mentor".
- Las cuentas de ejemplo tienen ciudades como "Ciudad de México / Online"; el sistema compara solo "Ciudad de México".
- `tests/smoke_test.py` creció con las comprobaciones de esta ronda (sección "v3").

---

## 3. Decisiones de comportamiento que el equipo debe conocer

| Decisión | Motivo |
| :--- | :--- |
| Una cuenta **solo mentor** no puede solicitar clases (el servidor lo bloquea). | Es lo que dice el registro ("Solo mentor: impartir asesorías"). Quien quiera ambas cosas elige "Ambas cosas". |
| El **correo del mentor ya no es público**; el teléfono solo se ve en sesiones aceptadas. | Privacidad: la plataforma atenderá estudiantes, posiblemente menores. |
| Al **aceptar** una solicitud, las otras pendientes que se traslapan se **rechazan solas**. | Evita solicitudes imposibles de aceptar. |
| Solo se puede **completar** una sesión que ya inició, y solo la completa el **mentor**. | Evita cerrar sesiones futuras. |
| Un mentor aparece en el directorio **solo con 1+ materia y 1+ horario**. | Si no puede recibir solicitudes, no debe aparecer. |
| Las **reseñas no se editan ni se borran**, y cada una pertenece a una sesión completada. | Que el puntaje sea creíble: no se puede inflar con cuentas falsas sin haber tomado sesiones, ni cambiar después. |
| El puntaje es el **promedio de las reseñas que traen estrellas**; las que solo traen comentario no cuentan para el promedio. | Las estrellas son opcionales. |
| Se muestra el nombre del estudiante **abreviado** ("Luis G."). | Privacidad, sobre todo si hay menores de edad. |
| `ocupacion` se conserva en la BD como **titular del perfil**. | Evita renombrar la columna en todos los archivos. |

---

## 4. Pruebas automáticas (opcional)

`tests/smoke_test.py` ejecuta 120 verificaciones de punta a punta (registro, solicitudes, conflictos, aceptar/rechazar/cancelar/completar, panel y reseñas). Requiere `pip install requests`, el servidor en `127.0.0.1:8123` y una **BD recién creada** (borrar `storage/*.sqlite`):

```bash
php -S 127.0.0.1:8123 -t public
python3 tests/smoke_test.py
```

> Las pruebas se corrieron contra esta versión y pasaron todas. **No se revisó el diseño visual en un navegador** (Tailwind carga por CDN); conviene que alguien del equipo revise las pantallas nuevas (`panel.php`, `agenda.php`, registro).

---

## 5. Pendiente / ideas que NO se implementaron

- **Reseñas (mejoras):** respuesta del mentor a una reseña, reportar reseñas inapropiadas, paginar o filtrar comentarios (hoy se ven las 20 más recientes) y editar una reseña dentro de un plazo corto.
- **Menores de edad:** pedir fecha de nacimiento y correo del tutor.
- **Términos de uso y aviso de privacidad** (casilla obligatoria en el registro).
- **Notificaciones** (correo o WhatsApp) cuando se acepta o rechaza una solicitud.
- **Verificación de mentores** (credencial o cédula) y foto de perfil.
- **Perfil del estudiante:** materias en las que necesita ayuda y editar sus propios datos.
- **Recuperar contraseña** e inicio de sesión con Google.
- Reprogramar una sesión (hoy solo se cancela y se vuelve a solicitar).
