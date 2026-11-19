# Historias de Usuario, Estados de Sesión y Modelo de Datos — MentorMatch

Documento técnico correspondiente a la **Revisión 1 (19 de noviembre de 2026)** del **Proyecto 06 — MentorMatch** (Red abierta de asesorías e intercambio de habilidades).

---

## 1. Roles del Sistema

* **Estudiante / Aprendiz:** Cualquier persona que busca mentores por materia o habilidad, consulta perfiles y horarios disponibles, solicita sesiones de asesoría y revisa el estado de sus solicitudes.
* **Mentor:** Usuario que publica las materias o habilidades que domina y sus bloques de horario disponibles, y gestiona la confirmación (aceptación, rechazo o cancelación) de solicitudes.
* **Rol Dual (`ambos`):** Conforme a la ficha técnica de MentorMatch, un mismo usuario puede asumir ambos perfiles desde su misma cuenta.

---

## 2. Historias de Usuario y Criterios de Aceptación (Backlog MVP)

### HU-01: Búsqueda y filtrado de mentores por materia o habilidad
* **Como:** Usuario en busca de asesoría.
* **Quiero:** Buscar mentores filtrando por materia exacta, modalidad (*Virtual* / *Presencial*) o palabra clave.
* **Para:** Encontrar rápidamente quién domina el tema o habilidad que necesito aprender.
* **Criterios de aceptación:**
  1. El buscador en `index.php` permite seleccionar una materia del catálogo o escribir un término libre.
  2. Si existen coincidencias, muestra las tarjetas con nombre, ocupación/especialidad, calificación, materias y horarios.
  3. Si la búsqueda no arroja resultados, muestra un mensaje comprensible de estado vacío y un botón para restablecer el catálogo (nunca una pantalla en blanco).

### HU-02: Registro abierto e inicio de sesión con soporte de rol dual
* **Como:** Persona interesada en aprender o enseñar.
* **Quiero:** Iniciar sesión o crear una cuenta con cualquier correo electrónico eligiendo si participo como estudiante/aprendiz, mentor o ambos perfiles.
* **Para:** Acceder de forma personalizada y segura a las funciones de solicitud y agenda.
* **Criterios de aceptación:**
  1. Las contraseñas se almacenan con `password_hash()` y se validan con `password_verify()`.
  2. Los formularios `POST` validan un token CSRF activo en sesión.
  3. No se permite duplicar cuentas con el mismo correo electrónico.

### HU-03: Consulta de perfil de mentor y espacios disponibles
* **Como:** Usuario interesado en una asesoría.
* **Quiero:** Consultar el perfil detallado de un mentor con su descripción, nivel de dominio por materia y bloques de horario disponibles.
* **Para:** Elegir el día, hora y modalidad que mejor se adapte a mi agenda.
* **Criterios de aceptación:**
  1. La vista `perfil.php?id=X` muestra la biografía del mentor, sus materias con descripción de temas y sus bloques horarios activos.
  2. Si se solicita un ID de mentor inexistente, el sistema muestra un estado de error controlado con enlace de retorno al catálogo.

### HU-04: Solicitud de asesoría con validación de fechas y conflictos
* **Como:** Usuario solicitante.
* **Quiero:** Enviar una solicitud de sesión indicando materia, horario disponible, fecha propuesta y tema específico.
* **Para:** Reservar un espacio de asesoría con el mentor.
* **Criterios de aceptación:**
  1. El sistema rechaza cualquier fecha anterior al día actual (`fecha_sesion < hoy`).
  2. El sistema comprueba mediante `tiene_conflicto_horario()` que el mentor no tenga otra sesión aceptada en la misma fecha y hora.
  3. Si los datos son válidos, registra/simula la solicitud con estado inicial `pendiente` y muestra el resumen con folio.

### HU-05: Gestión de estados de sesión y agenda personal
* **Como:** Mentor o estudiante implicado en una asesoría.
* **Quiero:** Consultar la agenda de sesiones futuras e historial y gestionar la aceptación, rechazo o cancelación de solicitudes.
* **Para:** Llevar control claro de mis asesorías y evitar dobles reservas.
* **Criterios de aceptación:**
  1. No se permite reservar dos sesiones simultáneas para el mismo mentor.
  2. Solo el mentor y el estudiante implicados pueden modificar o cancelar su sesión.
  3. La agenda distingue claramente los estados `pendiente`, `aceptada`, `rechazada`, `cancelada` y `completada`.

---

## 3. Modelo de Estados de una Sesión

| Estado | Tabla donde aplica | Qué significa | Regla de transición / validación |
| :--- | :--- | :--- | :--- |
| **`pendiente`** | `solicitudes` | El solicitante envió la petición para una fecha futura y bloque válido. | Estado inicial al crear una solicitud. |
| **`aceptada`** | `solicitudes` | El mentor aprobó la solicitud tras verificar que no hay choque de horario. | Crea automáticamente un registro en `sesiones` con estado `programada` y bloquea ese horario en esa fecha. |
| **`rechazada`** | `solicitudes` | El mentor declinó la solicitud por indisponibilidad. | El bloque horario permanece libre para otros usuarios. |
| **`programada`** | `sesiones` | Sesión futura confirmada en la agenda de ambos usuarios. | Impide aceptar otra solicitud para el mismo mentor en la misma `fecha_sesion` y `hora_inicio`. |
| **`cancelada`** | `solicitudes` / `sesiones` | Cancelada por el solicitante o el mentor implicado antes de realizarse. | Solo los usuarios implicados (`estudiante_id` o `mentor_id`) pueden ejecutar esta transición; libera el horario. |
| **`completada`** | `sesiones` | La asesoría se llevó a cabo y forma parte del historial. | Registra `notas_cierre` para consulta en el historial. |

---

## 4. Modelo de Datos Relacional (SQLite 3)

El archivo `database/schema.sql` define las **5 entidades sugeridas en la ficha oficial**:

1. **`usuarios`**: `id` (PK), `nombre`, `correo` (UNIQUE), `ocupacion`, `experiencia`, `rol` (`estudiante`, `mentor`, `ambos`), `biografia`, `modalidad`, `ubicacion`, `calificacion_promedio`, `password_hash`, `creado_en`.
2. **`habilidades`**: `id` (PK), `usuario_id` (FK → `usuarios.id`), `materia`, `area`, `nivel`, `descripcion_tema`.
3. **`disponibilidades`**: `id` (PK), `mentor_id` (FK → `usuarios.id`), `dia_semana`, `hora_inicio`, `hora_fin`, `lugar_o_enlace`, `modalidad`, `activo`.
4. **`solicitudes`**: `id` (PK), `estudiante_id` (FK → `usuarios.id`), `mentor_id` (FK → `usuarios.id`), `habilidad_id` (FK → `habilidades.id`), `disponibilidad_id` (FK → `disponibilidades.id`), `fecha_sesion`, `hora_inicio`, `hora_fin`, `modalidad`, `tema_especifico`, `mensaje_estudiante`, `estado`, `creado_en`.
5. **`sesiones`**: `id` (PK), `solicitud_id` (FK UNIQUE → `solicitudes.id`), `mentor_id` (FK → `usuarios.id`), `estudiante_id` (FK → `usuarios.id`), `materia`, `fecha_sesion`, `hora_inicio`, `hora_fin`, `lugar_o_enlace`, `estado`, `notas_cierre`, `actualizado_en`.
