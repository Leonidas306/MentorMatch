-- Datos Semilla de Demostración — Proyecto 06: MentorMatch (Abierto a todo público, sin datos personales reales)
-- Contraseña común de cuentas demo: demo1234
-- Categorías (area) estandarizadas: 'Ciencia', 'Matemáticas', 'Tecnología y Código', 'Diseño y Creatividad', 'Negocios y Finanzas', 'Idiomas'

INSERT INTO usuarios (id, nombre, correo, ocupacion, experiencia, rol, biografia, modalidad, ubicacion, calificacion_promedio, password_hash) VALUES
(1, 'Alejandro Morales', 'alejandro.morales@mentormatch.demo', 'Desarrollador Full-Stack & Instructor de Algoritmos', '5 años de experiencia', 'ambos', 'Apasionado por enseñar programación desde cero, estructuras de datos y matemáticas aplicadas con ejercicios prácticos.', 'Ambas', 'Ciudad de México / Online', 4.9, '__DEMO_HASH__'),
(2, 'Mariana Soto', 'mariana.soto@mentormatch.demo', 'Diseñadora UX/UI & Mentora Creativa', '4 años de experiencia', 'ambos', 'Ayudo a personas de cualquier perfil a dominar diseño de interfaces en Figma, prototipado interactivo y sistemas de diseño.', 'Virtual', 'Remoto / Online', 4.8, '__DEMO_HASH__'),
(3, 'Diego Vázquez', 'diego.vazquez@mentormatch.demo', 'Ingeniero Físico & Asesor de Ciencias', '6 años de experiencia', 'mentor', 'Asesorías personalizadas de física clásica, termodinámica y álgebra lineal para estudiantes o autodidactas.', 'Ambas', 'Guadalajara / Online', 4.7, '__DEMO_HASH__'),
(4, 'Valeria Ríos', 'valeria.rios@mentormatch.demo', 'Arquitecta de Base de Datos & Backend', '5 años de experiencia', 'ambos', 'Especialista en modelado SQL, optimización de consultas, PHP moderno y desarrollo de aplicaciones web seguras.', 'Ambas', 'Monterrey / Online', 5.0, '__DEMO_HASH__'),
(5, 'Carlos Mendoza', 'carlos.mendoza@mentormatch.demo', 'Profesor de Idiomas & Comunicación Global', '7 años de experiencia', 'mentor', 'Sesiones prácticas de inglés conversacional, preparación para entrevistas internacionales y redacción profesional.', 'Virtual', 'Remoto / Online', 4.8, '__DEMO_HASH__'),
(6, 'Sofía Hernández', 'sofia.hernandez@mentormatch.demo', 'Analista Financiera & Especialista en Estadística', '4 años de experiencia', 'ambos', 'Mentora en finanzas personales, Excel avanzado, probabilidad y estadística aplicada para toma de decisiones.', 'Ambas', 'Querétaro / Online', 4.9, '__DEMO_HASH__'),
(7, 'Luis Fernando Gómez', 'luis.gomez@mentormatch.demo', 'Emprendedor y Aprendiz Autodidacta', 'Nivel Inicial', 'estudiante', 'Aprendiendo desarrollo web, inglés conversacional y finanzas para lanzar mi propio negocio digital.', 'Ambas', 'Puebla / Online', 5.0, '__DEMO_HASH__');

INSERT INTO habilidades (id, usuario_id, materia, area, nivel, descripcion_tema) VALUES
(1, 1, 'Programación y Lógica desde Cero', 'Tecnología y Código', 'Especialista', 'Fundamentos de programación, estructuras de datos, algoritmos, resolución de problemas y buenas prácticas.'),
(2, 1, 'Cálculo y Matemáticas Aplicadas', 'Matemáticas', 'Avanzado', 'Límites, derivadas, integrales y razonamiento matemático explicado paso a paso sin tecnicismos innecesarios.'),
(3, 2, 'Diseño UX/UI y Figma', 'Diseño y Creatividad', 'Especialista', 'Diseño de interfaces web y móviles, wireframes, prototipos interactivos, accesibilidad y pruebas de usabilidad.'),
(4, 3, 'Física Clásica y Termodinámica', 'Ciencia', 'Especialista', 'Mecánica clásica, leyes de la termodinámica, energía, ondas y preparación para exámenes o proyectos científicos.'),
(5, 3, 'Álgebra Lineal y Geometría Analítica', 'Matemáticas', 'Especialista', 'Vectores, matrices, sistemas de ecuaciones lineales, espacios vectoriales y transformaciones.'),
(6, 4, 'Desarrollo Web (PHP, SQL y JavaScript)', 'Tecnología y Código', 'Especialista', 'Construcción de aplicaciones web completas, bases de datos relacionales SQLite/MySQL, PDO y seguridad web.'),
(7, 5, 'Inglés Conversacional y para Entrevistas', 'Idiomas', 'Especialista', 'Fluidez al hablar, vocabulario profesional, simulación de entrevistas laborales y corrección de pronunciación.'),
(8, 6, 'Finanzas Personales y Excel Avanzado', 'Negocios y Finanzas', 'Avanzado', 'Presupuestos, control de gastos, tablas dinámicas, fórmulas financieras y tableros de control.'),
(9, 6, 'Probabilidad, Estadística y Ciencia de Datos', 'Ciencia', 'Especialista', 'Análisis exploratorio de datos, distribuciones de probabilidad, método científico e interpretación de métricas.');

INSERT INTO disponibilidades (id, mentor_id, dia_semana, hora_inicio, hora_fin, lugar_o_enlace, modalidad, activo) VALUES
(1, 1, 'Lunes', '17:00', '18:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(2, 1, 'Miércoles', '18:00', '19:00', 'Coworking Central Sala B / Online', 'Presencial', 1),
(3, 1, 'Viernes', '16:00', '17:00', 'Sala Zoom / Google Meet', 'Virtual', 1),
(4, 2, 'Martes', '19:00', '20:00', 'Sesión en vivo por Figma + Google Meet', 'Virtual', 1),
(5, 2, 'Jueves', '18:00', '19:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(6, 3, 'Lunes', '16:00', '17:00', 'Biblioteca Pública Sala de Estudio 2', 'Presencial', 1),
(7, 3, 'Sábado', '10:00', '11:00', 'Pizarra interactiva vía Zoom', 'Virtual', 1),
(8, 4, 'Martes', '17:00', '18:00', 'Sala Microsoft Teams / Meet', 'Virtual', 1),
(9, 4, 'Jueves', '17:00', '18:00', 'Café Hub Tecnológico Mesa 4', 'Presencial', 1),
(10, 5, 'Miércoles', '20:00', '21:00', 'Sala de Conversación en Zoom', 'Virtual', 1),
(11, 6, 'Lunes', '18:00', '19:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(12, 6, 'Sábado', '11:00', '12:00', 'Centro Cultural Aula 3', 'Presencial', 1);

INSERT INTO solicitudes (id, estudiante_id, mentor_id, habilidad_id, disponibilidad_id, fecha_sesion, hora_inicio, hora_fin, modalidad, tema_especifico, mensaje_estudiante, estado) VALUES
(1, 7, 1, 1, 1, '2026-11-23', '17:00', '18:00', 'Virtual', 'Lógica de programación, arreglos y funciones', 'Hola Alejandro, quiero aprender a estructurar bien mis primeros programas.', 'aceptada'),
(2, 7, 4, 6, 8, '2026-11-24', '17:00', '18:00', 'Virtual', 'Conexión PHP con SQLite PDO y validación de formularios', 'Me gustaría repasar cómo guardar datos de forma segura.', 'pendiente'),
(3, 1, 2, 3, 4, '2026-11-24', '19:00', '20:00', 'Virtual', 'Mejora de interfaz y componentes responsivos', 'Quiero aprender a diseñar mejores tarjetas y formularios para mis proyectos web.', 'pendiente'),
(4, 7, 5, 7, 10, '2026-11-19', '20:00', '21:00', 'Virtual', 'Práctica de entrevista en inglés', 'Tenía otro compromiso a esa hora, solicito cambio.', 'rechazada'),
(5, 2, 6, 8, 11, '2026-11-16', '18:00', '19:00', 'Virtual', 'Organización de presupuesto para proyectos freelance', 'Sesión para aprender a cotizar y llevar control en Excel.', 'aceptada');

INSERT INTO sesiones (id, solicitud_id, mentor_id, estudiante_id, materia, fecha_sesion, hora_inicio, hora_fin, lugar_o_enlace, estado, notas_cierre) VALUES
(1, 1, 1, 7, 'Programación y Lógica desde Cero', '2026-11-23', '17:00', '18:00', 'Sala Google Meet (enlace al confirmar)', 'programada', 'Sesión confirmada. Tener editor de código abierto.'),
(2, 5, 6, 2, 'Finanzas Personales y Excel Avanzado', '2026-11-16', '18:00', '19:00', 'Sala Google Meet (enlace al confirmar)', 'completada', 'Se armó la plantilla de ingresos y gastos mensuales con fórmulas automatizadas.');
