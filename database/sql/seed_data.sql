-- Datos de ejemplo para rellenar la base de datos

-- =========================
-- FACULTAD
-- =========================
INSERT INTO Facultad (sigla, nombre_facultad) VALUES
('FICCT', 'FACULTAD DE INGENIERIA EN CIENCIAS DE LA COMPUTACIÓN Y TELECOMUNICACIONES');

-- =========================
-- CARRERA (códigos empezando en 1)
-- =========================
INSERT INTO Carrera (codigo, sigla, nombre_carrera) VALUES
(1, 'INF', 'Ingeniería Informática'),
(2, 'SIS', 'Ingeniería en Sistemas'),
(3, 'RED', 'Redes y Telecomunicaciones'),
(4, 'ROB', 'Robótica');

-- =========================
-- MATERIA (códigos 1..4)
-- =========================
INSERT INTO Materia (codigo, sigla, nombre_materia) VALUES
(1, 'FIS', 'Física'),
(2, 'MAT', 'Matemáticas'),
(3, 'ING', 'Inglés'),
(4, 'COM', 'Computación');

-- =========================
-- GESTION_ACADEMICA
-- =========================
INSERT INTO Gestion_Academica (codigo, anio, gestion) VALUES
(1, 2026, 'Gestión 1 - 2026');

-- =========================
-- HORARIO
-- =========================
INSERT INTO Horario (codigo, dia, hora_inicio, hora_fin) VALUES
(1, 'Lunes', '08:00', '10:00'),
(2, 'Martes', '10:00', '12:00'),
(3, 'Miércoles', '14:00', '16:00'),
(4, 'Jueves', '16:00', '18:00');

-- =========================
-- AULA
-- =========================
INSERT INTO Aula (nro, numero_aula, piso) VALUES
(1, 'A101', 1),
(2, 'A102', 1),
(3, 'B201', 2),
(4, 'B202', 2);

-- =========================
-- CUPO_CARRERA (vinculado a Carrera y Gestion_Academica)
-- =========================
INSERT INTO Cupo_Carrera (codigo, carrera_id, cupo_maximo, cupos_disponibles, gestion_academica_id) VALUES
(1, 1, 120, 120, 1),
(2, 2, 100, 100, 1),
(3, 3, 80, 80, 1),
(4, 4, 60, 60, 1);

-- =========================
-- PERSONA (26 registros, id explícito 1..26)
-- =========================
INSERT INTO Persona (id, ci, nombre, apellido, fecha_nacimiento, sexo, direccion, telefono, correo_electronico, ciudad) VALUES
(1,  '1001', 'Juan',     'Pérez',      '1990-01-01', 'M', 'Av. Libertad 123', '70000001', 'juan.perez@mail.com', 'Santa Cruz'),
(2,  '1002', 'María',    'López',      '1992-02-02', 'F', 'Av. Aroma 456',    '70000002', 'maria.lopez@mail.com', 'La Paz'),
(3,  '1003', 'Carlos',   'Gómez',      '1991-03-03', 'M', 'Av. Busch 789',    '70000003', 'carlos.gomez@mail.com', 'Cochabamba'),
(4,  '1004', 'Ana',      'Torrez',     '1993-04-04', 'F', 'Av. San Martín 321','70000004','ana.torrez@mail.com','Santa Cruz'),
(5,  '1005', 'Luis',     'Fernández',  '1994-05-05', 'M', 'Av. Beni 654',     '70000005', 'luis.fernandez@mail.com','La Paz'),
(6,  '1006', 'Sofía',    'Ramírez',    '1995-06-06', 'F', 'Calle 7 #45',      '70000006', 'sofia.ramirez@mail.com','Santa Cruz'),
(7,  '1007', 'Miguel',   'Vargas',     '1990-07-07', 'M', 'Calle 9 #12',      '70000007', 'miguel.vargas@mail.com','Cochabamba'),
(8,  '1008', 'Lucía',    'Quispe',     '1996-08-08', 'F', 'Av. Grigotá 22',   '70000008', 'lucia.quispe@mail.com','Santa Cruz'),
(9,  '1009', 'Diego',    'Rojas',      '1991-09-09', 'M', 'Calle 3 #78',      '70000009', 'diego.rojas@mail.com','La Paz'),
(10, '1010', 'Paola',    'Sánchez',    '1992-10-10', 'F', 'Av. 6 de Agosto 5', '70000010','paola.sanchez@mail.com','Santa Cruz'),
(11, '1011', 'Andrés',   'Mendoza',    '1993-11-11', 'M', 'Calle 12 #4',      '70000011', 'andres.mendoza@mail.com','Cochabamba'),
(12, '1012', 'Carla',    'Flores',     '1994-12-12', 'F', 'Av. 3 Pasaje 2',   '70000012', 'carla.flores@mail.com','Santa Cruz'),
(13, '1013', 'Jorge',    'Salazar',    '1990-01-15', 'M', 'Calle 20 #1',      '70000013', 'jorge.salazar@mail.com','La Paz'),
(14, '1014', 'Elena',    'Vega',       '1991-02-20', 'F', 'Av. 4 #99',        '70000014', 'elena.vega@mail.com','Cochabamba'),
(15, '1015', 'Raúl',     'Ortega',     '1992-03-25', 'M', 'Calle 2 #33',      '70000015', 'raul.ortega@mail.com','Santa Cruz'),
(16, '1016', 'Natalia',  'Cruz',       '1993-04-30', 'F', 'Av. 8 #11',        '70000016', 'natalia.cruz@mail.com','La Paz'),
(17, '1017', 'Fernando', 'Calani',     '1994-05-18', 'M', 'Calle 1 #7',       '70000017', 'fernando.calani@mail.com','Santa Cruz'),
(18, '1018', 'Verónica', 'Aranibar',   '1995-06-22', 'F', 'Av. 10 #20',       '70000018', 'veronica.aranibar@mail.com','Santa Cruz'),
(19, '1019', 'Pablo',    'Ramos',      '1990-07-30', 'M', 'Calle 14 #2',      '70000019', 'pablo.ramos@mail.com','Cochabamba'),
(20, '1020', 'Marta',    'Gutiérrez',  '1991-08-12', 'F', 'Av. 2 #44',        '70000020', 'marta.gutierrez@mail.com','La Paz'),
(21, '1021', 'Óscar',    'Suárez',     '1992-09-05', 'M', 'Calle 5 #66',      '70000021', 'oscar.suarez@mail.com','Santa Cruz'),
(22, '1022', 'Rosa',     'Paredes',    '1993-10-17', 'F', 'Av. 11 #8',        '70000022', 'rosa.paredes@mail.com','Cochabamba'),
(23, '1023', 'Hugo',     'Molina',     '1994-11-23', 'M', 'Calle 18 #3',      '70000023', 'hugo.molina@mail.com','La Paz'),
(24, '1024', 'Santiago', 'Loza',       '1995-12-02', 'M', 'Av. 7 #55',        '70000024', 'santiago.loza@mail.com','Santa Cruz'),
(25, '1025', 'Isabel',   'Pinto',      '1996-01-09', 'F', 'Calle 21 #10',     '70000025', 'isabel.pinto@mail.com','Cochabamba'),
(26, '1026', 'Diego',    'Alvarado',   '1997-02-14', 'M', 'Av. 13 #77',       '70000026', 'diego.alvarado@mail.com','La Paz');

-- =========================
-- ROL (ejemplos)
-- =========================
INSERT INTO Rol (codigo, nombre) VALUES
(1, 'Administrador'),
(2, 'Docente'),
(3, 'Coordinador'),
(4, 'Decano'),
(5, 'Administrativo');

-- =========================
-- PRIVILEGIO (ejemplos)
-- =========================
INSERT INTO Privilegio (codigo, nombre_privilegio) VALUES
(1, 'Gestionar Postulantes'),
(2, 'Registrar Notas'),
(3, 'Generar Reportes'),
(4, 'Administrar Usuarios');

-- =========================
-- DECANO (1 registro)
-- =========================
INSERT INTO Decano (id, codigo, fecha_designacion, titulo_profesional, sigla_facultad) VALUES
(1, 'DEC001', '2020-01-01', 'Doctor en Ciencias', 'FICCT');

-- =========================
-- DOCENTE (4 registros)
-- =========================
INSERT INTO Docente (id, codigo, especialidad, profesional_area, maestria, diplomado_educacion_superior, cantidad_grupos_asignados) VALUES
(1, 'DOC001', 'Matemáticas', 'Ciencias Exactas', 'Maestría en Educación', 'Diplomado en Docencia', 2),
(2, 'DOC002', 'Física', 'Ciencias Exactas', 'Maestría en Física', 'Diplomado en Educación', 1),
(3, 'DOC003', 'Computación', 'Ingeniería', 'Maestría en Informática', 'Diplomado en Educación', 2),
(4, 'DOC004', 'Inglés', 'Idiomas', 'Maestría en Lingüística', 'Diplomado en Educación', 1);

-- =========================
-- ADMINISTRATIVO (4 registros)
-- =========================
INSERT INTO Administrativo (id, codigo, horario_trabajo) VALUES
(1, 'ADM001', '08:00-16:00'),
(2, 'ADM002', '09:00-17:00'),
(3, 'ADM003', '07:00-15:00'),
(4, 'ADM004', '10:00-18:00');

-- =========================
-- COORDINADOR (2 registros)
-- =========================
INSERT INTO Coordinador (id, codigo, horario_trabajo) VALUES
(1, 'COO001', '08:00-16:00'),
(2, 'COO002', '09:00-17:00');

-- =========================
-- CARGA_HORARIA_DOCENTE (ejemplos)
-- =========================
INSERT INTO Carga_Horaria_Docente (id, horas_asignadas, codigo_docente) VALUES
(1, 12, 1),
(2, 8, 2),
(3, 14, 3),
(4, 6, 4);

-- =========================
-- GRUPO (asignamos 4 grupos ejemplo)
-- =========================
INSERT INTO Grupo (codigo, nombre_grupo, capacidad_maxima, codigo_materia, codigo_docente, codigo_horario) VALUES
(1, 'G1-INF-2026', 70, 4, 3, 1),
(2, 'G2-MAT-2026', 70, 2, 1, 2),
(3, 'G3-FIS-2026', 70, 2, 2, 3),
(4, 'G4-ING-2026', 70, 3, 4, 4);

-- =========================
-- INSCRIPCION (15 inscripciones para 15 postulantes)
-- =========================
INSERT INTO Inscripcion (codigo, fecha_inscripcion, estado_pago, codigo_gestion_academica, codigo_pago, codigo_pasarelaPago) VALUES
(1, '2026-01-10', 'Pagado', 1, NULL, NULL),
(2, '2026-01-11', 'Pagado', 1, NULL, NULL),
(3, '2026-01-12', 'Pendiente', 1, NULL, NULL),
(4, '2026-01-13', 'Pagado', 1, NULL, NULL),
(5, '2026-01-14', 'Pagado', 1, NULL, NULL),
(6, '2026-01-15', 'Pagado', 1, NULL, NULL),
(7, '2026-01-16', 'Pendiente', 1, NULL, NULL),
(8, '2026-01-17', 'Pagado', 1, NULL, NULL),
(9, '2026-01-18', 'Pagado', 1, NULL, NULL),
(10,'2026-01-19', 'Pagado', 1, NULL, NULL),
(11,'2026-01-20', 'Pendiente', 1, NULL, NULL),
(12,'2026-01-21', 'Pagado', 1, NULL, NULL),
(13,'2026-01-22', 'Pagado', 1, NULL, NULL),
(14,'2026-01-23', 'Pagado', 1, NULL, NULL),
(15,'2026-01-24', 'Pagado', 1, NULL, NULL);

-- =========================
-- PAGO (ejemplos; dejamos codigo_inscripcion NULL para evitar ciclo FK)
-- =========================
INSERT INTO Pago (codigo, monto, fecha_pago, comprobante, codigo_inscripcion) VALUES
(1, 150.00, '2026-01-10', 'REC-0001', NULL),
(2, 150.00, '2026-01-11', 'REC-0002', NULL),
(3, 150.00, '2026-01-13', 'REC-0003', NULL),
(4, 150.00, '2026-01-14', 'REC-0004', NULL),
(5, 150.00, '2026-01-15', 'REC-0005', NULL);

-- =========================
-- PASARELA_PAGO (ejemplos; codigo_pago NULL o referenciar pagos existentes)
-- =========================
INSERT INTO Pasarela_Pago (codigo, monto, fecha_pago, comprobante, codigo_pago) VALUES
(1, 150.00, '2026-01-10', 'TP-0001', 1),
(2, 150.00, '2026-01-11', 'TP-0002', 2),
(3, 150.00, '2026-01-13', 'TP-0003', 3);

-- =========================
-- POSTULANTE (15 registros) - vinculados a inscripciones y grupos y carreras
-- =========================
INSERT INTO Postulante (id, registro, colegio_procedencia, ciudad, titulo_bachiller, otros_requisitos, codigo_inscripcion, codigo_grupo, carrera_primera_opcion_id, carrera_segunda_opcion_id, carrera_asignada_id, estado_asignacion) VALUES
(1, 'P001', 'Colegio Nacional', 'Santa Cruz', 'Sí', 'Ninguno', 1, 1, 1, 2, NULL, 'Pendiente'),
(2, 'P002', 'Colegio Alemán', 'La Paz', 'Sí', 'Ninguno', 2, 1, 2, 3, NULL, 'Pendiente'),
(3, 'P003', 'Colegio San Antonio', 'Cochabamba', 'Sí', 'Ninguno', 3, 2, 3, 4, NULL, 'Pendiente'),
(4, 'P004', 'Colegio Cristo Rey', 'Santa Cruz', 'Sí', 'Ninguno', 4, 2, 4, 1, NULL, 'Pendiente'),
(5, 'P005', 'Colegio San José', 'Santa Cruz', 'Sí', 'Ninguno', 5, 3, 1, 3, NULL, 'Pendiente'),
(6, 'P006', 'Colegio Santa María', 'La Paz', 'Sí', 'Ninguno', 6, 3, 2, 1, NULL, 'Pendiente'),
(7, 'P007', 'Colegio Don Bosco', 'Cochabamba', 'Sí', 'Ninguno', 7, 4, 3, 2, NULL, 'Pendiente'),
(8, 'P008', 'Colegio San Miguel', 'Santa Cruz', 'Sí', 'Ninguno', 8, 4, 4, 1, NULL, 'Pendiente'),
(9, 'P009', 'Colegio La Salle', 'La Paz', 'Sí', 'Ninguno', 9, 1, 1, 4, NULL, 'Pendiente'),
(10,'P010', 'Colegio Nacional 2', 'Santa Cruz', 'Sí', 'Ninguno', 10, 2, 2, 3, NULL, 'Pendiente'),
(11,'P011', 'Colegio Central', 'Cochabamba', 'Sí', 'Ninguno', 11, 3, 3, 1, NULL, 'Pendiente'),
(12,'P012', 'Colegio Libertad', 'La Paz', 'Sí', 'Ninguno', 12, 4, 4, 2, NULL, 'Pendiente'),
(13,'P013', 'Colegio Nuevo', 'Santa Cruz', 'Sí', 'Ninguno', 13, 1, 1, 2, NULL, 'Pendiente'),
(14,'P014', 'Colegio San Pedro', 'Cochabamba', 'Sí', 'Ninguno', 14, 2, 2, 4, NULL, 'Pendiente'),
(15,'P015', 'Colegio Santa Ana', 'La Paz', 'Sí', 'Ninguno', 15, 3, 3, 1, NULL, 'Pendiente');

-- =========================
-- EXAMEN (ejemplos: cada postulante rinde 1 examen por materia; aquí 15 exámenes de muestra)
-- =========================
INSERT INTO Examen (codigo, fecha_examen, registro_postulante) VALUES
(1, '2026-02-10', 1),
(2, '2026-02-10', 2),
(3, '2026-02-11', 3),
(4, '2026-02-11', 4),
(5, '2026-02-12', 5),
(6, '2026-02-12', 6),
(7, '2026-02-13', 7),
(8, '2026-02-13', 8),
(9, '2026-02-14', 9),
(10,'2026-02-14',10),
(11,'2026-02-15',11),
(12,'2026-02-15',12),
(13,'2026-02-16',13),
(14,'2026-02-16',14),
(15,'2026-02-17',15);

-- =========================
-- CONFIGURACION_PORCENTAJES (ejemplo global)
-- =========================
INSERT INTO Configuracion_Porcentajes (codigo, porcentaje_examen1, porcentaje_examen2, porcentaje_examen3, codigo_examen) VALUES
(1, 30, 30, 40, NULL);

-- =========================
-- CALIFICACION (ejemplos: una nota por examen; nota entre 0 y 100)
-- =========================
INSERT INTO Calificacion (id, nota, registro_postulante, codigo_examen) VALUES
(1, 78, 1, 1),
(2, 85, 2, 2),
(3, 62, 3, 3),
(4, 55, 4, 4),
(5, 90, 5, 5),
(6, 47, 6, 6),
(7, 73, 7, 7),
(8, 66, 8, 8),
(9, 81, 9, 9),
(10, 59, 10, 10),
(11, 77, 11, 11),
(12, 88, 12, 12),
(13, 69, 13, 13),
(14, 92, 14, 14),
(15, 54, 15, 15);

-- =========================
-- DOCUMENTOS (ejemplos)
-- =========================
INSERT INTO Documentos (codigo, tipo_documento, codigo_docente, registro_postulante) VALUES
(1, 'Título Bachiller', NULL, 1),
(2, 'Certificado de Estudios', NULL, 2),
(3, 'Título Profesional', 1, NULL),
(4, 'Diploma', 3, NULL);

-- =========================
-- CONTROL_ASIGNACION_CARRERA (ejemplos)
-- =========================
INSERT INTO Control_Asignacion_Carrera (codigo, postulante_id, carrera_asignada_id, fecha_asignacion, es_segunda_opcion, prioridad, observacion) VALUES
(1, 1, 1, '2026-03-01', FALSE, 1, 'Asignado a primera opción'),
(2, 2, 2, '2026-03-01', FALSE, 1, 'Asignado a primera opción'),
(3, 3, 3, '2026-03-02', FALSE, 1, 'Asignado a primera opción');

-- =========================
-- REPORTE (ejemplos)
-- =========================
INSERT INTO Reporte (codigo, tipo_reporte, fecha_generacion, formato, id_persona) VALUES
(1, 'Lista Postulantes', '2026-03-05', 'PDF', 1),
(2, 'Postulantes Aprobados', '2026-03-06', 'Excel', 2);

-- =========================
-- BITACORA (ejemplos)
-- =========================
INSERT INTO Bitacora (codigo, accion, fecha_hora, ip_origen, id_persona) VALUES
(1, 'Creación de postulante P001', '2026-01-10 09:00:00', '192.168.1.10', 1),
(2, 'Registro de pago P002', '2026-01-11 10:15:00', '192.168.1.11', 2);

-- =========================
-- LOTE_USUARIOS (ejemplos)
-- =========================
INSERT INTO Lote_Usuarios (codigo, archivo_csv, fecha_carga, usuario_carga, id_persona) VALUES
(1, 'lote_usuarios_enero.csv', '2026-01-05', 'admin', 1);

-- =========================
-- ESTADISTICA (ejemplo)
-- =========================
INSERT INTO Estadistica (codigo, total_inscritos, total_aprobados, total_reprobados, total_grupos_habilitados) VALUES
(1, 15, 9, 6, 1);

-- =========================
-- CONFIGURACION_PORCENTAJES (registro adicional si se desea por examen)
-- =========================
INSERT INTO Configuracion_Porcentajes (codigo, porcentaje_examen1, porcentaje_examen2, porcentaje_examen3, codigo_examen) VALUES
(2, 30, 30, 40, NULL);
