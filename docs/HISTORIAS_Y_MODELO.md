# Historias de Usuario, Estados de Sesión y Modelo de Datos — MentorMatch

Actualizado para la **Revisión 2 (26 de noviembre de 2026)**.

---

## 1. Roles

* **Estudiante:** busca mentores, solicita asesorías, cancela y reseña sus sesiones.
* **Mentor:** publica materias y horarios, acepta o rechaza solicitudes y completa sesiones. Una cuenta solo mentor no puede solicitar asesorías.
* **Ambos:** puede hacer lo de los dos roles con la misma cuenta.

---

## 2. Historias de usuario y criterios de aceptación

### HU-01: Búsqueda y filtrado de mentores
**Como** estudiante **quiero** buscar por texto, categoría, nivel educativo y modalidad **para** encontrar quién domina el tema.
1. Solo aparecen mentores con al menos 1 materia y 1 horario activo.
2. Sin resultados se muestra un estado vacío con opción de restablecer.
3. Con el filtro *Presencial* y una ciudad registrada, solo aparecen mentores de esa ciudad.

### HU-02: Registro e inicio de sesión
**Como** visitante **quiero** crear una cuenta como estudiante, mentor o ambos **para** usar la plataforma.
1. Contraseñas con `password_hash()` y `password_verify()`.
2. Se validan nombre, correo, contraseña, rol y nivel educativo en el servidor.
3. No se permiten correos duplicados.

### HU-03: Perfil de mentor
**Como** estudiante **quiero** ver materias, horarios, tarifa y reseñas **para** elegir un mentor.
1. El perfil público no muestra el correo ni el teléfono.
2. Un id inexistente muestra un mensaje controlado.

### HU-04: Solicitud de asesoría
**Como** estudiante **quiero** pedir un bloque y una fecha **para** reservar una asesoría.
1. La solicitud se guarda en SQLite con estado `pendiente`.
2. Se rechazan fechas pasadas, fechas que no caen en el día del bloque y bloques de hoy que ya iniciaron.
3. Se rechazan choques con sesiones aceptadas (del mentor o del estudiante) y solicitudes duplicadas.
4. Un bloque presencial exige que ambos sean de la misma ciudad.

### HU-05: Agenda y estados
**Como** participante **quiero** ver mis solicitudes y cambiar su estado **para** controlar mis asesorías.
1. La agenda exige sesión y muestra solo lo propio.
2. El mentor acepta o rechaza; el estudiante cancela pendientes; ambos pueden cancelar aceptadas cuya fecha no haya pasado.
3. Solo los implicados pueden modificar una sesión.

### HU-06: Panel del mentor
**Como** mentor **quiero** editar mi perfil, materias y horarios **para** aparecer en el directorio.
1. No se admiten horarios traslapados ni con fin anterior al inicio.
2. Un bloque presencial requiere lugar.
3. No se elimina una materia con solicitudes; los horarios se dan de baja sin borrar historial.

### HU-07: Reseñas verificadas
**Como** estudiante **quiero** calificar una sesión completada **para** orientar a otros.
1. Una reseña por sesión, solo del estudiante de esa sesión.
2. Estrellas (1 a 5) y comentario (máximo 500) son opcionales, pero debe haber al menos uno.
3. El promedio del mentor se calcula desde las reseñas.

---

## 3. Estados de una sesión

| Estado | Tabla | Cómo se llega | Regla |
| :--- | :--- | :--- | :--- |
| `pendiente` | `solicitudes` | El estudiante envía la solicitud | No bloquea el horario |
| `aceptada` | `solicitudes` | El mentor acepta | Revisa choques de ambos, crea la sesión y rechaza las pendientes que choquen |
| `rechazada` | `solicitudes` | El mentor rechaza, o rechazo automático por choque | El horario queda libre |
| `programada` | `sesiones` | Se crea al aceptar | Una sola sesión por solicitud |
| `cancelada` | `solicitudes` y `sesiones` | La cancela un implicado | El horario queda libre |
| `completada` | `sesiones` | El mentor la marca cuando ya inició | Permite notas de cierre y reseña |

```text
pendiente ──aceptar──> aceptada (sesión programada) ──completar──> completada ──> reseña opcional
    ├──rechazar──> rechazada          └──cancelar──> cancelada
    └──cancelar──> cancelada
```

---

## 4. Modelo de datos (SQLite)

| Tabla | Campos principales |
| :--- | :--- |
| `usuarios` | `id`, `nombre`, `correo` (único), `ocupacion` (titular), `experiencia`, `nivel_educativo`, `nivel_otro`, `rol`, `biografia`, `modalidad`, `ubicacion`, `telefono`, `tarifa_hora`, `password_hash`, `creado_en` |
| `habilidades` | `id`, `usuario_id` → usuarios, `materia`, `area`, `nivel`, `descripcion_tema` |
| `disponibilidades` | `id`, `mentor_id` → usuarios, `dia_semana`, `hora_inicio`, `hora_fin`, `lugar_o_enlace`, `modalidad`, `activo` |
| `solicitudes` | `id`, `estudiante_id`, `mentor_id`, `habilidad_id`, `disponibilidad_id`, `fecha_sesion`, `hora_inicio`, `hora_fin`, `modalidad`, `tema_especifico`, `mensaje_estudiante`, `estado`, `creado_en` |
| `sesiones` | `id`, `solicitud_id` (único), `mentor_id`, `estudiante_id`, `materia`, `fecha_sesion`, `hora_inicio`, `hora_fin`, `lugar_o_enlace`, `estado`, `notas_cierre`, `actualizado_en` |
| `resenas` | `id`, `sesion_id` (único), `mentor_id`, `estudiante_id`, `calificacion`, `comentario`, `creado_en` |

### Cambios respecto a la Revisión 1
* `usuarios`: se agregan `nivel_educativo`, `nivel_otro`, `telefono` y `tarifa_hora`; se elimina `calificacion_promedio`.
* `habilidades.nivel` ahora es el nivel educativo al que se da la clase.
* Tabla nueva `resenas`.
