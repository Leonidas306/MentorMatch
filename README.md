# Proyecto 06 — MentorMatch · Red de Asesorías e Intercambio de Habilidades

Aplicación web desarrollada para la materia de **Aplicaciones de Internet (2026)**. **MentorMatch** es una plataforma abierta a todo el público donde cualquier persona (estudiante, profesional o autodidacta) puede ofrecer o solicitar asesorías organizadas en **6 grandes categorías** (*Ciencia*, *Matemáticas*, *Tecnología y Código*, *Diseño y Creatividad*, *Negocios y Finanzas* e *Idiomas*).

---

## 1. Problema a Resolver

Las personas y estudiantes que necesitan asesoría en una materia o habilidad específica no conocen fácilmente quién domina el tema ni cuál es su disponibilidad de horario, dependiendo de anuncios dispersos sin control de agenda.

## 2. Objetivo del Producto

Publicar habilidades y horarios disponibles, explorar mentores mediante una barra interactiva de categorías y buscador por materia, solicitar sesiones de asesoría y gestionar confirmaciones dentro de la aplicación, permitiendo que un mismo usuario pueda asumir tanto el perfil de **mentor** como el de **estudiante/aprendiz**.

## 3. Alcance Funcional Obligatorio (MVP)

1. **Perfil de mentor con materia, categoría y descripción:** Cada mentor cuenta con biografía, especialidad/ocupación, experiencia, insignia automática `✓ Perfil Completo` (cuando registra biografía, al menos 1 materia y 1 horario) y lista de materias clasificadas por área y nivel.
2. **Barra de 6 Categorías y Búsqueda por Materia:** Barra visual estilo cápsula con iconos SVG para filtrar mentores por categoría (*Ciencia*, *Matemáticas*, *Tecnología y Código*, *Diseño y Creatividad*, *Negocios y Finanzas*, *Idiomas*), sincronizada con el selector de materias, modalidad (*Virtual* / *Presencial*) y buscador instantáneo en JavaScript.
3. **Publicación de espacios disponibles:** Bloques horarios por día de la semana, hora de inicio/fin, modalidad y lugar o sala virtual.
4. **Solicitud, aceptación o cancelación de sesiones:** Flujo validado que impide fechas pasadas y previene conflictos de horario (doble reserva simultánea para el mismo mentor).
5. **Listado de sesiones futuras e historial:** Vista de agenda con el ciclo completo de estados (`pendiente`, `aceptada`, `rechazada`, `cancelada`, `completada`).

---

## 4. Tecnologías Utilizadas (Variante A Autorizada)

* **Frontend:** HTML5 semántico, **Tailwind CSS** (paleta editorial Índigo / Violeta / Teal), tipografía *Plus Jakarta Sans* (Google Fonts) y **JavaScript Vanilla**.
* **Backend y Persistencia:** **PHP puro 8.x** + **SQLite 3** mediante **PDO** con consultas preparadas.
* **Seguridad:** Sesiones nativas PHP (`$_SESSION`), `password_hash()` y `password_verify()`, protección **CSRF** en todos los formularios `POST` y escape de salida HTML con `htmlspecialchars()`.

---

## 5. Instrucciones de Ejecución Local (Paso a Paso)

### Requisitos previos
* Tener instalado **PHP 8.x** con la extensión **`pdo_sqlite`** habilitada:
  ```bash
  php -v
  php -m
  ```

### Pasos para levantar el proyecto
1. Clonar el repositorio y ubicarse en la carpeta raíz del proyecto:
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   cd mentormatch
   ```
2. Iniciar el servidor integrado de PHP apuntando a la carpeta `public/`:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
3. Abrir en el navegador:
   ```text
   http://127.0.0.1:8000
   ```
   > **Nota de auto-inicialización:** Al abrir la aplicación por primera vez, `src/bootstrap.php` crea automáticamente el archivo `storage/mentormatch.sqlite` y ejecuta `database/schema.sql` y `database/seed.sql` con 6 mentores de demostración distribuidos en las 6 categorías, 9 materias y 12 horarios disponibles.

### Cuentas de Demostración Incluidas
En la pantalla de **Iniciar Sesión** (`http://127.0.0.1:8000/login.php`) puedes ingresar con un clic usando:
* **Mentor y Estudiante (Tecnología y Código / Matemáticas):** `alejandro.morales@mentormatch.demo` / Contraseña: `demo1234`
* **Mentora y Estudiante (Tecnología y Código):** `valeria.rios@mentormatch.demo` / Contraseña: `demo1234`
* **Estudiante / Aprendiz:** `luis.gomez@mentormatch.demo` / Contraseña: `demo1234`

---

## 6. Equipo de Desarrollo (5 Integrantes)

| Integrante | Rol / Módulo Asignado | Rama `feature/*` (Revisión 1) |
| :--- | :--- | :--- |
| **Leonardo** | Líder de Repositorio, Configuración Base (`bootstrap.php`) y Documentación | `feature/e1-config-y-documentacion` |
| **Jose Pepe** | Esquema SQLite (`schema.sql`), Datos Semilla (`seed.sql`) y Repositorio PDO (`repository.php`) | `feature/e1-modelo-datos-y-semilla` |
| **David Aram** | Autenticación con Rol Dual (`auth.php`, `login.php`, `logout.php`) y Layout Responsivo (`layout.php`) | `feature/e1-auth-y-layout` |
| **Cutberto** | Landing, Barra de 6 Categorías, Tarjetas Editoriales y Buscador (`index.php`, `assets/app.js`) | `feature/e1-catalogo-y-busqueda` |
| **Yuliana** | Perfil de Mentor, Horarios, Solicitud Simulada y Agenda de Estados (`perfil.php`, `agenda.php`) | `feature/e1-perfil-y-solicitud` |
