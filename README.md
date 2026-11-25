# Proyecto 06 — MentorMatch · Asesorías de Regularización Escolar

Aplicación web de la materia **Aplicaciones de Internet (2026)**. **MentorMatch** es una plataforma de clases de regularización donde se registran **mentores** y **estudiantes**, organizada en 6 categorías: *Matemáticas*, *Física y Química*, *Biología*, *Español y Humanidades*, *Inglés e Idiomas* y *Programación y Tecnología*.

---

## 1. Problema a resolver

Los estudiantes que necesitan asesoría no conocen fácilmente quién domina una materia ni su disponibilidad.

## 2. Objetivo del producto

Publicar materias y horarios, solicitar sesiones de asesoría y gestionar su confirmación dentro de la aplicación. Un mismo usuario puede ser mentor y estudiante.

## 3. Alcance funcional (MVP) y estado en la Revisión 2

| Función | Dónde |
| :--- | :--- |
| Registro e inicio de sesión (estudiante, mentor o ambos) | `login.php` |
| Catálogo con búsqueda y filtros por categoría, nivel y modalidad | `index.php` |
| Perfil del mentor y solicitud de asesoría guardada en SQLite | `perfil.php` |
| Panel del mentor: perfil, materias, horarios y solicitudes | `panel.php` |
| Aceptar, rechazar, cancelar y completar sesiones | `agenda.php`, `panel.php`, `accion_sesion.php` |
| Bloqueo de choques de horario (para mentor y estudiante) | `src/repository.php` |
| Reseñas verificadas de sesiones completadas (opcionales) | `agenda.php`, `perfil.php` |

Fuera de alcance: videollamadas, pagos y mensajería privada. La tarifa por hora es solo informativa.

## 4. Tecnologías (Variante A)

* **Frontend:** HTML5, Tailwind CSS (Play CDN, solo para desarrollo), fuente *Plus Jakarta Sans* y JavaScript sin frameworks.
* **Backend y persistencia:** PHP puro 8.x y SQLite mediante PDO con consultas preparadas.
* **Seguridad:** sesiones PHP, `password_hash()` / `password_verify()`, token CSRF en formularios `POST` y escape de salida con `htmlspecialchars()`.
* **API externa:** no se usa. La ficha del proyecto marca las invitaciones por correo como opcionales y permite sustituirlas por confirmaciones dentro de la aplicación.

## 5. Instalación y ejecución local

Requisitos: **PHP 8.x** con la extensión `pdo_sqlite` (`php -v`, `php -m`) y un navegador con internet (para Tailwind y la fuente).

```bash
git clone <URL_DEL_REPOSITORIO>
cd mentormatch
git checkout develop
php -S 127.0.0.1:8000 -t public
```

Abrir `http://127.0.0.1:8000`.

**Base de datos:** en la primera visita se crea `storage/mentormatch.sqlite` con `database/schema.sql` y `database/seed.sql`. Si existe una base con el esquema de la Revisión 1, se respalda como `mentormatch.old-FECHA.sqlite` y se crea una nueva. Para reiniciar los datos de demostración, detener el servidor y borrar `storage/mentormatch.sqlite`.

### Cuentas de demostración (contraseña `demo1234`)

| Correo | Rol |
| :--- | :--- |
| `alejandro.morales@mentormatch.demo` | Mentor y estudiante |
| `valeria.rios@mentormatch.demo` | Mentora y estudiante |
| `diego.vazquez@mentormatch.demo` | Solo mentor |
| `luis.gomez@mentormatch.demo` | Solo estudiante |

> Los datos semilla usan fechas fijas de noviembre de 2026. Para demostrar *aceptar / rechazar*, crear una solicitud nueva con fecha futura.

## 6. Pruebas

* Matriz de casos manuales: [`docs/ENTREGABLE_2_PLANTILLA.md`](docs/ENTREGABLE_2_PLANTILLA.md).
* Prueba automática de punta a punta (requiere `pip install requests`, el servidor en el puerto 8123 y una base recién creada):

```bash
php -S 127.0.0.1:8123 -t public
python3 tests/smoke_test.py    # en otra terminal; en Windows: python tests/smoke_test.py
```

## 7. Documentación

* [`docs/HISTORIAS_Y_MODELO.md`](docs/HISTORIAS_Y_MODELO.md): historias de usuario, estados y modelo de datos.
* [`README_CAMBIOS.md`](README_CAMBIOS.md): detalle de los cambios posteriores a la Revisión 1.

## 8. Equipo de desarrollo

| Integrante | Módulo | Rama Revisión 1 | Rama Revisión 2 |
| :--- | :--- | :--- | :--- |
| **Leonardo** | Configuración base y documentación | `feature/e1-config-y-documentacion` | `feature/e2-config-y-documentacion` |
| **Jose Pepe** | Esquema SQLite, semilla y repositorio PDO | `feature/e1-modelo-datos-y-semilla` | `feature/e2-persistencia-y-reglas` |
| **David Aram** | Autenticación, layout y panel del mentor | `feature/e1-auth-y-layout` | `feature/e2-registro-y-panel-mentor` |
| **Cutberto** | Portada, catálogo, filtros y JavaScript | `feature/e1-catalogo-y-busqueda` | `feature/e2-filtros-y-catalogo` |
| **Yuliana** | Perfil, solicitudes y agenda | `feature/e1-perfil-y-solicitud` | `feature/e2-solicitudes-y-agenda` |
