# Plantilla de Entrega — Revisión 2 (26 de noviembre de 2026)

> Completar los campos entre corchetes con enlaces y resultados reales antes de entregar.

* **Proyecto:** 06 — MentorMatch (Red de asesorías e intercambio de habilidades), enfocado en regularización escolar
* **Equipo e integrantes (Equipo 5):**
  1. Juan Leonardo Rivera Morales (Integrante 1 — Configuración base y documentación)
  2. [Nombre completo de Jose Pepe] (Integrante 2 — SQLite y repositorio PDO)
  3. [Nombre completo de David Aram] (Integrante 3 — Registro, layout y panel del mentor)
  4. [Nombre completo de Cutberto] (Integrante 4 — Catálogo y filtros)
  5. [Nombre completo de Yuliana] (Integrante 5 — Perfil, solicitudes y agenda)
* **Revisión / fecha:** Revisión 2 · Jueves 26 de noviembre de 2026 (35 puntos)
* **Repositorio GitHub:** [enlace]
* **Rama de integración:** `develop`
* **URL de demostración:** no aplica (ejecución local con `php -S`)

---

## 1. Objetivo de esta entrega

Guardar de forma persistente mentores, materias, horarios, solicitudes, sesiones y reseñas en SQLite, y terminar el flujo principal de punta a punta: solicitar, aceptar o rechazar, cancelar y completar una asesoría, con validaciones de fechas, conflictos de horario y permisos.

---

## 2. Funcionalidades implementadas

| Historia/Issue | Qué hace | Responsable | Rama feature | PR | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| HU-00 / #[ ] | Avisos, protección de rutas, migración de la base y documentación | Leonardo | `feature/e2-config-y-documentacion` | #[ ] | [ ] |
| HU-04, HU-07 / #[ ] | Esquema, semilla, escrituras PDO, estados, conflictos y reseñas | Jose Pepe | `feature/e2-persistencia-y-reglas` | #[ ] | [ ] |
| HU-02, HU-06 / #[ ] | Registro, navegación y panel del mentor | David Aram | `feature/e2-registro-y-panel-mentor` | #[ ] | [ ] |
| HU-01 / #[ ] | Catálogo con filtros combinados y formularios dinámicos | Cutberto | `feature/e2-filtros-y-catalogo` | #[ ] | [ ] |
| HU-03, HU-04, HU-05 / #[ ] | Solicitud real, agenda y cambios de estado | Yuliana | `feature/e2-solicitudes-y-agenda` | #[ ] | [ ] |

---

## 3. Instrucciones para ejecutar

1. **Requisitos:** PHP 8.x con `pdo_sqlite` y navegador con internet.
2. **Clonar:** `git clone <URL>`, `cd mentormatch`, `git checkout develop`.
3. **Variables o secretos:** no se requieren.
4. **SQLite:** se crea sola en `storage/` a partir de `database/schema.sql` y `database/seed.sql`.
5. **Servidor:** `php -S 127.0.0.1:8000 -t public`.
6. **URL y cuentas:** `http://127.0.0.1:8000`; `luis.gomez@mentormatch.demo` (estudiante) y `alejandro.morales@mentormatch.demo` (mentor), contraseña `demo1234`.

---

## 4. Modelo de datos y cambios en esta revisión

* Columnas nuevas en `usuarios`: `nivel_educativo`, `nivel_otro`, `telefono`, `tarifa_hora`.
* Tabla nueva `resenas`; el promedio del mentor se calcula en lugar de guardarse.
* **Escrituras:** registro, perfil, materias, horarios, solicitudes, sesiones y reseñas.
* **Lecturas:** catálogo filtrado, perfil, agenda y estadísticas.
* Detalle en `docs/HISTORIAS_Y_MODELO.md`.

### Enriquecimiento funcional propio (en lugar de API externa)

No se usa API externa: la ficha marca las invitaciones por correo como opcionales y acepta confirmaciones dentro de la aplicación. La lógica propia es: detección de traslapes de horario para ambos participantes, rechazo automático de pendientes que chocan, regla de ciudad para clases presenciales y promedio y distribución de reseñas.

---

## 5. Evidencia de pruebas manuales

Antes de empezar: borrar `storage/mentormatch.sqlite` y levantar el servidor. Ejecutar en orden. En "Resultado obtenido" escribir lo que **ustedes** observen y guardar una captura en `docs/evidencias/`.

| ID | Acción | Resultado esperado | Resultado obtenido | Evidencia | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| T-01 | Como Luis, en `perfil.php?id=1` elegir el bloque del **Lunes**, dejar la fecha sugerida, escribir un tema y enviar | Aviso "Solicitud enviada…"; aparece como *Pendiente* en Mi Agenda | [ ] | [ ] | [ ] |
| T-02 | Detener el servidor (Ctrl+C), levantarlo de nuevo, entrar como Luis y abrir Mi Agenda | La solicitud de T-01 sigue ahí | [ ] | [ ] | [ ] |
| T-03 **(negativa, permisos)** | Entrar como Luis y abrir `panel.php` | Redirige al inicio: "El panel es solo para mentores…" | [ ] | [ ] | [ ] |
| T-04 | Como Alejandro, aceptar la solicitud de T-01 | "Solicitud aceptada. La sesión quedó programada." | [ ] | [ ] | [ ] |
| T-05 **(negativa, doble reserva)** | Como Mariana (`mariana.soto@mentormatch.demo`), pedir a Alejandro el mismo Lunes y la misma fecha | "El mentor ya tiene una sesión aceptada el … que choca con ese horario…" | [ ] | [ ] | [ ] |
| T-06 **(negativa, fecha pasada)** | Como Luis, pedir el bloque del Viernes escribiendo la fecha de ayer (si el navegador bloquea la fecha, capturar ese bloqueo) | No se guarda; el servidor responde "No se aceptan fechas anteriores a hoy…" | [ ] | [ ] | [ ] |
| T-07 | Como Luis, pedir el bloque del **Viernes**; como Alejandro, rechazarlo | Estado *Rechazada*; "El horario quedó libre…" | [ ] | [ ] | [ ] |
| T-08 **(negativa, horario)** | Como Alejandro, en Mi Panel agregar Domingo 10:00–11:00 y después Domingo 10:30–11:30 | El primero se publica; el segundo muestra "Ese bloque se traslapa…" | [ ] | [ ] | [ ] |
| T-09 | Como Alejandro, marcar como completada la sesión del 23-nov; como Luis, dejar una reseña | La sesión pasa a Historial y la reseña aparece en el perfil | [ ] | [ ] | [ ] |

---

## 6. GitHub: enlaces

| Elemento | Enlace |
| :--- | :--- |
| Rama `develop` | [ ] |
| PR 1 a 5 | [ ] |
| Issues | [ ] |
| Commits principales | [ ] |
| Release | No aplica en esta revisión (se hace en la Revisión 3) |

---

## 7. Problemas conocidos, pendientes y riesgos

* Una solicitud pendiente **de hoy cuya hora ya pasó** todavía puede aceptarse: `responder_solicitud()` compara la fecha, pero no la hora. Pendiente de corregir.
* Los datos semilla tienen fechas fijas: las solicitudes pendientes de ejemplo del 24-nov ya no pueden aceptarse.
* Tailwind se carga con el Play CDN (solo desarrollo) y requiere internet.
* No hay notificaciones por correo ni recuperación de contraseña.
* Pendiente para la Revisión 3: matriz final de ocho pruebas, `release/1.0.0` y etiqueta `v1.0.0`.

---

## 8. Participación por integrante

| Integrante | Tarea | Commit(s) | Evidencia (PR / captura) | PR que revisó |
| :--- | :--- | :--- | :--- | :--- |
| Leonardo | Base y documentación | [ ] | [ ] | Jose Pepe |
| Jose Pepe | Datos y reglas | [ ] | [ ] | David Aram |
| David Aram | Registro y panel | [ ] | [ ] | Cutberto |
| Cutberto | Catálogo y filtros | [ ] | [ ] | Yuliana |
| Yuliana | Solicitudes y agenda | [ ] | [ ] | Leonardo |

---

## 9. Guion de demostración (aprox. 8 minutos)

| Min. | Quién | Qué muestra |
| :--- | :--- | :--- |
| 0–1 | Leonardo | Repositorio, ramas, PR, arranque del proyecto y protección de rutas (T-03) |
| 1–3 | David Aram | Registro de un mentor; panel: materia, horario y traslape (T-08) |
| 3–4 | Cutberto | Filtros del catálogo, estado vacío y formulario dinámico |
| 4–6 | Yuliana | Solicitar, aceptar y rechazar desde la agenda (T-01, T-04, T-07) |
| 6–8 | Jose Pepe | Tablas, persistencia tras reiniciar (T-02) y doble reserva (T-05) |
