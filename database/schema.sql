

PRAGMA foreign_keys = ON;

-- 1. Tabla de Usuarios 
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    correo TEXT NOT NULL UNIQUE,
    ocupacion TEXT NOT NULL DEFAULT 'Autodidacta / Profesional',
    experiencia TEXT NOT NULL DEFAULT 'Intermedio',
    rol TEXT NOT NULL DEFAULT 'ambos' CHECK (rol IN ('estudiante', 'mentor', 'ambos')),
    biografia TEXT DEFAULT '',
    modalidad TEXT NOT NULL DEFAULT 'Ambas' CHECK (modalidad IN ('Presencial', 'Virtual', 'Ambas')),
    ubicacion TEXT NOT NULL DEFAULT 'Remoto / Online',
    calificacion_promedio REAL NOT NULL DEFAULT 5.0 CHECK (calificacion_promedio >= 1.0 AND calificacion_promedio <= 5.0),
    password_hash TEXT NOT NULL,
    creado_en TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- 2. Tabla de Habilidades 
CREATE TABLE IF NOT EXISTS habilidades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    materia TEXT NOT NULL,
    area TEXT NOT NULL DEFAULT 'General',
    nivel TEXT NOT NULL DEFAULT 'Avanzado' CHECK (nivel IN ('Intermedio', 'Avanzado', 'Especialista')),
    descripcion_tema TEXT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 3. Tabla de Disponibilidades 
CREATE TABLE IF NOT EXISTS disponibilidades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mentor_id INTEGER NOT NULL,
    dia_semana TEXT NOT NULL CHECK (dia_semana IN ('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')),
    hora_inicio TEXT NOT NULL,
    hora_fin TEXT NOT NULL,
    lugar_o_enlace TEXT NOT NULL,
    modalidad TEXT NOT NULL DEFAULT 'Virtual' CHECK (modalidad IN ('Presencial', 'Virtual')),
    activo INTEGER NOT NULL DEFAULT 1 CHECK (activo IN (0, 1)),
    FOREIGN KEY (mentor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 4. Solicitudes de Asesoría
CREATE TABLE IF NOT EXISTS solicitudes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    mentor_id INTEGER NOT NULL,
    habilidad_id INTEGER NOT NULL,
    disponibilidad_id INTEGER,
    fecha_sesion TEXT NOT NULL,
    hora_inicio TEXT NOT NULL,
    hora_fin TEXT NOT NULL,
    modalidad TEXT NOT NULL DEFAULT 'Virtual' CHECK (modalidad IN ('Presencial', 'Virtual')),
    tema_especifico TEXT NOT NULL,
    mensaje_estudiante TEXT DEFAULT '',
    estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'aceptada', 'rechazada', 'cancelada')),
    creado_en TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (habilidad_id) REFERENCES habilidades(id) ON DELETE CASCADE,
    FOREIGN KEY (disponibilidad_id) REFERENCES disponibilidades(id) ON DELETE SET NULL
);

-- 5. Sesiones Confirmadas e Historial
CREATE TABLE IF NOT EXISTS sesiones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    solicitud_id INTEGER NOT NULL UNIQUE,
    mentor_id INTEGER NOT NULL,
    estudiante_id INTEGER NOT NULL,
    materia TEXT NOT NULL,
    fecha_sesion TEXT NOT NULL,
    hora_inicio TEXT NOT NULL,
    hora_fin TEXT NOT NULL,
    lugar_o_enlace TEXT NOT NULL,
    estado TEXT NOT NULL DEFAULT 'programada' CHECK (estado IN ('programada', 'completada', 'cancelada')),
    notas_cierre TEXT DEFAULT '',
    actualizado_en TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_habilidades_materia ON habilidades(materia);
CREATE INDEX IF NOT EXISTS idx_solicitudes_mentor_fecha ON solicitudes(mentor_id, fecha_sesion, hora_inicio);
CREATE INDEX IF NOT EXISTS idx_sesiones_mentor_horario ON sesiones(mentor_id, fecha_sesion, hora_inicio, estado);
