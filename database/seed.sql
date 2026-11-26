INSERT INTO usuarios (id, nombre, correo, ocupacion, experiencia, nivel_educativo, rol, biografia, modalidad, ubicacion, telefono, tarifa_hora, password_hash) VALUES
(1, 'Alejandro Morales', 'alejandro.morales@mentormatch.demo', 'Ing. en Sistemas & Tutor de Matemáticas y Programación', '5 años de experiencia', 'Universidad', 'ambos', 'Ayudo a regularizar álgebra, cálculo y lógica de programación desde cero, con ejercicios prácticos y explicaciones paso a paso.', 'Ambas', 'Ciudad de México / Online', '', 150, '__DEMO_HASH__'),
(2, 'Mariana Soto', 'mariana.soto@mentormatch.demo', 'Lic. en Letras & Tutora de Español', '4 años de experiencia', 'Universidad', 'ambos', 'Te apoyo con redacción, ortografía y comprensión lectora para secundaria y preparatoria, con técnicas para exámenes y ensayos.', 'Virtual', 'Remoto / Online', '', 120, '__DEMO_HASH__'),
(3, 'Diego Vázquez', 'diego.vazquez@mentormatch.demo', 'Ingeniero Físico & Asesor de Ciencias', '6 años de experiencia', 'Universidad', 'mentor', 'Asesorías personalizadas de física, termodinámica y álgebra lineal para estudiantes de preparatoria y primeros semestres.', 'Ambas', 'Guadalajara / Online', '', 180, '__DEMO_HASH__'),
(4, 'Valeria Ríos', 'valeria.rios@mentormatch.demo', 'Arquitecta de Base de Datos & Backend', '5 años de experiencia', 'Universidad', 'ambos', 'Especialista en modelado SQL, PHP y desarrollo web para estudiantes de carreras técnicas que necesitan regularizar sus materias.', 'Ambas', 'Monterrey / Online', '', 200, '__DEMO_HASH__'),
(5, 'Carlos Mendoza', 'carlos.mendoza@mentormatch.demo', 'Profesor de Inglés', '7 años de experiencia', 'Universidad', 'mentor', 'Clases de gramática y conversación en inglés para acreditar materias, exámenes de ubicación y preparación para certificaciones.', 'Virtual', 'Remoto / Online', '', 0, '__DEMO_HASH__'),
(6, 'Sofía Hernández', 'sofia.hernandez@mentormatch.demo', 'Bióloga & Tutora de Ciencias Naturales', '4 años de experiencia', 'Universidad', 'ambos', 'Mentora en biología, genética y estadística aplicada. Preparo a estudiantes para exámenes de admisión y de regularización.', 'Ambas', 'Querétaro / Online', '', 130, '__DEMO_HASH__'),
(7, 'Luis Fernando Gómez', 'luis.gomez@mentormatch.demo', 'Estudiante de Universidad', 'Estudiante', 'Universidad', 'estudiante', 'Necesito regularizar programación y matemáticas para ponerme al corriente con mi carrera.', 'Ambas', 'Puebla / Online', '', 0, '__DEMO_HASH__');

INSERT INTO habilidades (id, usuario_id, materia, area, nivel, descripcion_tema) VALUES
(1, 1, 'Programación desde Cero', 'Programación y Tecnología', 'Preparatoria', 'Lógica de programación, variables, ciclos, arreglos, funciones y resolución de problemas paso a paso.'),
(2, 1, 'Álgebra y Cálculo Diferencial', 'Matemáticas', 'Universidad', 'Álgebra, límites, derivadas y aplicaciones, explicado sin tecnicismos innecesarios y con ejercicios de examen.'),
(3, 2, 'Redacción y Comprensión Lectora', 'Español y Humanidades', 'Secundaria', 'Ortografía, redacción de ensayos y reportes, análisis de textos y técnicas de estudio para exámenes.'),
(4, 3, 'Física: Mecánica y Termodinámica', 'Física y Química', 'Preparatoria', 'Cinemática, leyes de Newton, energía, calor y termodinámica con problemas resueltos.'),
(5, 3, 'Álgebra Lineal y Geometría Analítica', 'Matemáticas', 'Universidad', 'Vectores, matrices, sistemas de ecuaciones lineales, espacios vectoriales y transformaciones.'),
(6, 4, 'Bases de Datos y Desarrollo Web', 'Programación y Tecnología', 'Universidad', 'Modelado relacional, SQL, PHP con PDO, JavaScript y seguridad básica en aplicaciones web.'),
(7, 5, 'Inglés: Gramática y Conversación', 'Inglés e Idiomas', 'Todos los niveles', 'Gramática, vocabulario, práctica oral y preparación para exámenes de acreditación.'),
(8, 6, 'Biología Celular y Genética', 'Biología', 'Preparatoria', 'Célula, ciclo celular, herencia mendeliana, ADN y repaso para exámenes de admisión.'),
(9, 6, 'Probabilidad y Estadística', 'Matemáticas', 'Universidad', 'Estadística descriptiva, distribuciones de probabilidad, inferencia e interpretación de resultados.');

INSERT INTO disponibilidades (id, mentor_id, dia_semana, hora_inicio, hora_fin, lugar_o_enlace, modalidad, activo) VALUES
(1, 1, 'Lunes', '17:00', '18:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(2, 1, 'Miércoles', '18:00', '19:00', 'Coworking Central Sala B / Online', 'Presencial', 1),
(3, 1, 'Viernes', '16:00', '17:00', 'Sala Zoom / Google Meet', 'Virtual', 1),
(4, 2, 'Martes', '19:00', '20:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(5, 2, 'Jueves', '18:00', '19:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(6, 3, 'Lunes', '16:00', '17:00', 'Biblioteca Pública Sala de Estudio 2', 'Presencial', 1),
(7, 3, 'Sábado', '10:00', '11:00', 'Pizarra interactiva vía Zoom', 'Virtual', 1),
(8, 4, 'Martes', '17:00', '18:00', 'Sala Microsoft Teams / Meet', 'Virtual', 1),
(9, 4, 'Jueves', '17:00', '18:00', 'Café Hub Tecnológico Mesa 4', 'Presencial', 1),
(10, 5, 'Miércoles', '20:00', '21:00', 'Sala de Conversación en Zoom', 'Virtual', 1),
(11, 6, 'Lunes', '18:00', '19:00', 'Sala Google Meet (enlace al confirmar)', 'Virtual', 1),
(12, 6, 'Sábado', '11:00', '12:00', 'Centro Cultural Aula 3', 'Presencial', 1);

INSERT INTO solicitudes (id, estudiante_id, mentor_id, habilidad_id, disponibilidad_id, fecha_sesion, hora_inicio, hora_fin, modalidad, tema_especifico, mensaje_estudiante, estado) VALUES
(1, 7, 1, 1, 1, '2026-11-23', '17:00', '18:00', 'Virtual', 'Lógica de programación, arreglos y funciones', 'Hola Alejandro, quiero regularizarme en programación y estructurar bien mis primeros programas.', 'aceptada'),
(2, 7, 4, 6, 8, '2026-11-24', '17:00', '18:00', 'Virtual', 'Conexión PHP con SQLite PDO y validación de formularios', 'Me gustaría repasar cómo guardar datos de forma segura.', 'pendiente'),
(3, 1, 2, 3, 4, '2026-11-24', '19:00', '20:00', 'Virtual', 'Redacción de ensayos y reportes técnicos', 'Quiero mejorar la redacción de mis reportes para entregar mejores trabajos.', 'pendiente'),
(4, 7, 5, 7, 10, '2026-11-19', '20:00', '21:00', 'Virtual', 'Práctica de conversación en inglés', 'Tenía otro compromiso a esa hora, solicito cambio.', 'rechazada'),
(5, 2, 6, 8, 11, '2026-11-16', '18:00', '19:00', 'Virtual', 'Repaso de genética para examen de regularización', 'Necesito repasar herencia mendeliana antes de mi examen extraordinario.', 'aceptada');

INSERT INTO sesiones (id, solicitud_id, mentor_id, estudiante_id, materia, fecha_sesion, hora_inicio, hora_fin, lugar_o_enlace, estado, notas_cierre) VALUES
(1, 1, 1, 7, 'Programación desde Cero', '2026-11-23', '17:00', '18:00', 'Sala Google Meet (enlace al confirmar)', 'programada', 'Sesión confirmada. Tener editor de código abierto.'),
(2, 5, 6, 2, 'Biología Celular y Genética', '2026-11-16', '18:00', '19:00', 'Sala Google Meet (enlace al confirmar)', 'completada', 'Se resolvieron cuadros de Punnett y se dejó una guía de ejercicios para el examen.');

INSERT INTO solicitudes (id, estudiante_id, mentor_id, habilidad_id, disponibilidad_id, fecha_sesion, hora_inicio, hora_fin, modalidad, tema_especifico, mensaje_estudiante, estado) VALUES
(6, 7, 1, 2, 3, '2026-11-13', '16:00', '17:00', 'Virtual', 'Derivadas y regla de la cadena', 'Tengo examen de cálculo la próxima semana.', 'aceptada'),
(7, 2, 1, 1, 1, '2026-11-09', '17:00', '18:00', 'Virtual', 'Ciclos y funciones en Python', '', 'aceptada'),
(8, 7, 4, 6, 8, '2026-11-10', '17:00', '18:00', 'Virtual', 'Consultas SQL con JOIN', 'No entiendo bien cuándo usar LEFT JOIN.', 'aceptada'),
(9, 1, 3, 4, 7, '2026-11-14', '10:00', '11:00', 'Virtual', 'Leyes de Newton y fricción', '', 'aceptada'),
(10, 7, 5, 7, 10, '2026-11-11', '20:00', '21:00', 'Virtual', 'Gramática: tiempos verbales', 'Quiero perder el miedo a hablar.', 'aceptada'),
(11, 1, 6, 9, 12, '2026-11-14', '11:00', '12:00', 'Presencial', 'Distribución normal y probabilidad', '', 'aceptada'),
(12, 7, 2, 3, 4, '2026-11-10', '19:00', '20:00', 'Virtual', 'Estructura de un ensayo', 'Me cuesta organizar mis ideas al escribir.', 'aceptada'),
(13, 6, 1, 2, 2, '2026-11-11', '18:00', '19:00', 'Presencial', 'Factorización y ecuaciones', '', 'aceptada'),
(14, 2, 3, 5, 6, '2026-11-02', '16:00', '17:00', 'Presencial', 'Matrices y determinantes', '', 'aceptada');

INSERT INTO sesiones (id, solicitud_id, mentor_id, estudiante_id, materia, fecha_sesion, hora_inicio, hora_fin, lugar_o_enlace, estado, notas_cierre) VALUES
(3, 6, 1, 7, 'Álgebra y Cálculo Diferencial', '2026-11-13', '16:00', '17:00', 'Sala Zoom / Google Meet', 'completada', 'Se practicó la regla de la cadena con 10 ejercicios.'),
(4, 7, 1, 2, 'Programación desde Cero', '2026-11-09', '17:00', '18:00', 'Sala Google Meet (enlace al confirmar)', 'completada', 'Ciclos for/while y funciones con parámetros.'),
(5, 8, 4, 7, 'Bases de Datos y Desarrollo Web', '2026-11-10', '17:00', '18:00', 'Sala Microsoft Teams / Meet', 'completada', 'INNER, LEFT y RIGHT JOIN con tablas de ejemplo.'),
(6, 9, 3, 1, 'Física: Mecánica y Termodinámica', '2026-11-14', '10:00', '11:00', 'Pizarra interactiva vía Zoom', 'completada', 'Planos inclinados y fricción estática y cinética.'),
(7, 10, 5, 7, 'Inglés: Gramática y Conversación', '2026-11-11', '20:00', '21:00', 'Sala de Conversación en Zoom', 'completada', 'Present perfect vs. past simple, con práctica oral.'),
(8, 11, 6, 1, 'Probabilidad y Estadística', '2026-11-14', '11:00', '12:00', 'Centro Cultural Aula 3', 'completada', 'Distribución normal, z-scores y ejercicios de tablas.'),
(9, 12, 2, 7, 'Redacción y Comprensión Lectora', '2026-11-10', '19:00', '20:00', 'Sala Google Meet (enlace al confirmar)', 'completada', 'Introducción, desarrollo y conclusión; se dejó plantilla.'),
(10, 13, 1, 6, 'Álgebra y Cálculo Diferencial', '2026-11-11', '18:00', '19:00', 'Coworking Central Sala B / Online', 'completada', 'Factorización por factor común y trinomios.'),
(11, 14, 3, 2, 'Álgebra Lineal y Geometría Analítica', '2026-11-02', '16:00', '17:00', 'Biblioteca Pública Sala de Estudio 2', 'completada', 'Determinantes 2x2 y 3x3.');

INSERT INTO resenas (id, sesion_id, mentor_id, estudiante_id, calificacion, comentario, creado_en) VALUES
(1, 2, 6, 2, 5, 'Me explicó la genética con ejemplos muy claros. Aprobé mi examen extraordinario.', '2026-11-17 10:12:00'),
(2, 3, 1, 7, 5, 'Alejandro explica paso a paso y deja ejercicios de práctica. Ya entiendo las derivadas.', '2026-11-14 09:30:00'),
(3, 4, 1, 2, 5, '', '2026-11-10 12:05:00'),
(4, 5, 4, 7, 5, 'Muy clara con SQL, resolvió todas mis dudas de JOIN. Puntual y paciente.', '2026-11-11 18:40:00'),
(5, 6, 3, 1, 4, 'Buen repaso de fricción y planos inclinados. Faltó un poco de tiempo para más ejercicios.', '2026-11-15 11:20:00'),
(6, 7, 5, 7, 5, 'Clases dinámicas, me ayudó a perder el miedo a hablar en inglés.', '2026-11-12 08:55:00'),
(7, 8, 6, 1, 5, 'Excelente para estadística, relaciona los temas con ejemplos de la vida real.', '2026-11-15 13:00:00'),
(8, 9, 2, 7, 5, 'Me dio una plantilla para estructurar ensayos que me sirvió muchísimo.', '2026-11-11 21:10:00'),
(9, 10, 1, 6, 4, 'Buena explicación de factorización, aunque la sesión presencial fue algo corta.', '2026-11-12 10:45:00'),
(10, 11, 3, 2, NULL, 'Domina el tema y tiene mucha paciencia con las matrices.', '2026-11-03 09:15:00');