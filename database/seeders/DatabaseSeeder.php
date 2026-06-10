<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // FACULTAD
        DB::table('facultad')->insert([
            ['sigla' => 'FICCT', 'nombre_facultad' => 'FACULTAD DE INGENIERIA EN CIENCIAS DE LA COMPUTACIÓN Y TELECOMUNICACIONES'],
        ]);

        // CARRERA
        DB::table('carrera')->insert([
            ['codigo' => 1, 'sigla' => 'INF', 'nombre_carrera' => 'Ingeniería Informática', 'facultad_sigla' => 'FICCT'],
            ['codigo' => 2, 'sigla' => 'SIS', 'nombre_carrera' => 'Ingeniería en Sistemas', 'facultad_sigla' => 'FICCT'],
            ['codigo' => 3, 'sigla' => 'RED', 'nombre_carrera' => 'Redes y Telecomunicaciones', 'facultad_sigla' => 'FICCT'],
            ['codigo' => 4, 'sigla' => 'ROB', 'nombre_carrera' => 'Robótica', 'facultad_sigla' => 'FICCT'],
        ]);

        // MATERIA
        DB::table('materia')->insert([
            ['codigo' => 1, 'sigla' => 'FIS', 'nombre_materia' => 'Física'],
            ['codigo' => 2, 'sigla' => 'MAT', 'nombre_materia' => 'Matemáticas'],
            ['codigo' => 3, 'sigla' => 'ING', 'nombre_materia' => 'Inglés'],
            ['codigo' => 4, 'sigla' => 'COM', 'nombre_materia' => 'Computación'],
        ]);

        // GESTION_ACADEMICA
        DB::table('gestion_academica')->insert([
            ['codigo' => 1, 'anio' => 2026, 'gestion' => 'Gestión 1 - 2026'],
        ]);

        // HORARIO
        DB::table('horario')->insert([
            ['codigo' => 1, 'dia' => 'Lunes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
            ['codigo' => 2, 'dia' => 'Martes', 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
            ['codigo' => 3, 'dia' => 'Miércoles', 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
            ['codigo' => 4, 'dia' => 'Jueves', 'hora_inicio' => '16:00', 'hora_fin' => '18:00'],
            ['codigo' => 5, 'dia' => 'Lunes', 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
            ['codigo' => 6, 'dia' => 'Martes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
        ]);

        // AULA
        DB::table('aula')->insert([
            ['nro' => 1, 'numero_aula' => 'A101', 'piso' => 1],
            ['nro' => 2, 'numero_aula' => 'A102', 'piso' => 1],
            ['nro' => 3, 'numero_aula' => 'B201', 'piso' => 2],
            ['nro' => 4, 'numero_aula' => 'B202', 'piso' => 2],
        ]);

        // CUPO_CARRERA
        DB::table('cupo_carrera')->insert([
            ['codigo' => 1, 'carrera_id' => 1, 'cupo_maximo' => 120, 'cupos_disponibles' => 120, 'gestion_academica_id' => 1],
            ['codigo' => 2, 'carrera_id' => 2, 'cupo_maximo' => 100, 'cupos_disponibles' => 100, 'gestion_academica_id' => 1],
            ['codigo' => 3, 'carrera_id' => 3, 'cupo_maximo' => 80,  'cupos_disponibles' => 80,  'gestion_academica_id' => 1],
            ['codigo' => 4, 'carrera_id' => 4, 'cupo_maximo' => 60,  'cupos_disponibles' => 60,  'gestion_academica_id' => 1],
        ]);

        // PERSONA
        DB::table('persona')->insert([
            ['ci' => '1001', 'nombre' => 'Juan', 'apellido' => 'Pérez', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'M', 'direccion' => 'Av. Libertad 123', 'telefono' => '70000001', 'correo_electronico' => 'juan.perez@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1002', 'nombre' => 'María', 'apellido' => 'López', 'fecha_nacimiento' => '1992-02-02', 'sexo' => 'F', 'direccion' => 'Av. Aroma 456', 'telefono' => '70000002', 'correo_electronico' => 'maria.lopez@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1003', 'nombre' => 'Carlos', 'apellido' => 'Gómez', 'fecha_nacimiento' => '1991-03-03', 'sexo' => 'M', 'direccion' => 'Av. Busch 789', 'telefono' => '70000003', 'correo_electronico' => 'carlos.gomez@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1004', 'nombre' => 'Ana', 'apellido' => 'Torrez', 'fecha_nacimiento' => '1993-04-04', 'sexo' => 'F', 'direccion' => 'Av. San Martín 321', 'telefono' => '70000004', 'correo_electronico' => 'ana.torrez@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1005', 'nombre' => 'Luis', 'apellido' => 'Fernández', 'fecha_nacimiento' => '1994-05-05', 'sexo' => 'M', 'direccion' => 'Av. Beni 654', 'telefono' => '70000005', 'correo_electronico' => 'luis.fernandez@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1006', 'nombre' => 'Sofía', 'apellido' => 'Ramírez', 'fecha_nacimiento' => '1995-06-06', 'sexo' => 'F', 'direccion' => 'Calle 7 #45', 'telefono' => '70000006', 'correo_electronico' => 'sofia.ramirez@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1007', 'nombre' => 'Miguel', 'apellido' => 'Vargas', 'fecha_nacimiento' => '1990-07-07', 'sexo' => 'M', 'direccion' => 'Calle 9 #12', 'telefono' => '70000007', 'correo_electronico' => 'miguel.vargas@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1008', 'nombre' => 'Lucía', 'apellido' => 'Quispe', 'fecha_nacimiento' => '1996-08-08', 'sexo' => 'F', 'direccion' => 'Av. Grigotá 22', 'telefono' => '70000008', 'correo_electronico' => 'lucia.quispe@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1009', 'nombre' => 'Diego', 'apellido' => 'Rojas', 'fecha_nacimiento' => '1991-09-09', 'sexo' => 'M', 'direccion' => 'Calle 3 #78', 'telefono' => '70000009', 'correo_electronico' => 'diego.rojas@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1010', 'nombre' => 'Paola', 'apellido' => 'Sánchez', 'fecha_nacimiento' => '1992-10-10', 'sexo' => 'F', 'direccion' => 'Av. 6 de Agosto 5', 'telefono' => '70000010', 'correo_electronico' => 'paola.sanchez@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1011', 'nombre' => 'Andrés', 'apellido' => 'Mendoza', 'fecha_nacimiento' => '1993-11-11', 'sexo' => 'M', 'direccion' => 'Calle 12 #4', 'telefono' => '70000011', 'correo_electronico' => 'andres.mendoza@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1012', 'nombre' => 'Carla', 'apellido' => 'Flores', 'fecha_nacimiento' => '1994-12-12', 'sexo' => 'F', 'direccion' => 'Av. 3 Pasaje 2', 'telefono' => '70000012', 'correo_electronico' => 'carla.flores@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1013', 'nombre' => 'Jorge', 'apellido' => 'Salazar', 'fecha_nacimiento' => '1990-01-15', 'sexo' => 'M', 'direccion' => 'Calle 20 #1', 'telefono' => '70000013', 'correo_electronico' => 'jorge.salazar@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1014', 'nombre' => 'Elena', 'apellido' => 'Vega', 'fecha_nacimiento' => '1991-02-20', 'sexo' => 'F', 'direccion' => 'Av. 4 #99', 'telefono' => '70000014', 'correo_electronico' => 'elena.vega@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1015', 'nombre' => 'Raúl', 'apellido' => 'Ortega', 'fecha_nacimiento' => '1992-03-25', 'sexo' => 'M', 'direccion' => 'Calle 2 #33', 'telefono' => '70000015', 'correo_electronico' => 'raul.ortega@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1016', 'nombre' => 'Natalia', 'apellido' => 'Cruz', 'fecha_nacimiento' => '1993-04-30', 'sexo' => 'F', 'direccion' => 'Av. 8 #11', 'telefono' => '70000016', 'correo_electronico' => 'natalia.cruz@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1017', 'nombre' => 'Fernando', 'apellido' => 'Calani', 'fecha_nacimiento' => '1994-05-18', 'sexo' => 'M', 'direccion' => 'Calle 1 #7', 'telefono' => '70000017', 'correo_electronico' => 'fernando.calani@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1018', 'nombre' => 'Verónica', 'apellido' => 'Aranibar', 'fecha_nacimiento' => '1995-06-22', 'sexo' => 'F', 'direccion' => 'Av. 10 #20', 'telefono' => '70000018', 'correo_electronico' => 'veronica.aranibar@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1019', 'nombre' => 'Pablo', 'apellido' => 'Ramos', 'fecha_nacimiento' => '1990-07-30', 'sexo' => 'M', 'direccion' => 'Calle 14 #2', 'telefono' => '70000019', 'correo_electronico' => 'pablo.ramos@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1020', 'nombre' => 'Marta', 'apellido' => 'Gutiérrez', 'fecha_nacimiento' => '1991-08-12', 'sexo' => 'F', 'direccion' => 'Av. 2 #44', 'telefono' => '70000020', 'correo_electronico' => 'marta.gutierrez@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1021', 'nombre' => 'Óscar', 'apellido' => 'Suárez', 'fecha_nacimiento' => '1992-09-05', 'sexo' => 'M', 'direccion' => 'Calle 5 #66', 'telefono' => '70000021', 'correo_electronico' => 'oscar.suarez@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1022', 'nombre' => 'Rosa', 'apellido' => 'Paredes', 'fecha_nacimiento' => '1993-10-17', 'sexo' => 'F', 'direccion' => 'Av. 11 #8', 'telefono' => '70000022', 'correo_electronico' => 'rosa.paredes@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1023', 'nombre' => 'Hugo', 'apellido' => 'Molina', 'fecha_nacimiento' => '1994-11-23', 'sexo' => 'M', 'direccion' => 'Calle 18 #3', 'telefono' => '70000023', 'correo_electronico' => 'hugo.molina@mail.com', 'ciudad' => 'La Paz'],
            ['ci' => '1024', 'nombre' => 'Santiago', 'apellido' => 'Loza', 'fecha_nacimiento' => '1995-12-02', 'sexo' => 'M', 'direccion' => 'Av. 7 #55', 'telefono' => '70000024', 'correo_electronico' => 'santiago.loza@mail.com', 'ciudad' => 'Santa Cruz'],
            ['ci' => '1025', 'nombre' => 'Isabel', 'apellido' => 'Pinto', 'fecha_nacimiento' => '1996-01-09', 'sexo' => 'F', 'direccion' => 'Calle 21 #10', 'telefono' => '70000025', 'correo_electronico' => 'isabel.pinto@mail.com', 'ciudad' => 'Cochabamba'],
            ['ci' => '1026', 'nombre' => 'Diego', 'apellido' => 'Alvarado', 'fecha_nacimiento' => '1997-02-14', 'sexo' => 'M', 'direccion' => 'Av. 13 #77', 'telefono' => '70000026', 'correo_electronico' => 'diego.alvarado@mail.com', 'ciudad' => 'La Paz'],
        ]);

        // ROL
        DB::table('rol')->insert([
            ['codigo' => 1, 'nombre' => 'Administrador'],
            ['codigo' => 2, 'nombre' => 'Docente'],
            ['codigo' => 3, 'nombre' => 'Coordinador'],
            ['codigo' => 4, 'nombre' => 'Decano'],
            ['codigo' => 5, 'nombre' => 'Administrativo'],
        ]);

        // ROL_GRUPO
        DB::table('rol_grupo')->insert([
            ['nombre_grupo' => 'Docente', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Administrativo', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Coordinador', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Postulante', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // PRIVILEGIO
        DB::table('privilegio')->insert([
            ['codigo' => 1, 'nombre_privilegio' => 'Gestionar Postulantes'],
            ['codigo' => 2, 'nombre_privilegio' => 'Registrar Notas'],
            ['codigo' => 3, 'nombre_privilegio' => 'Generar Reportes'],
            ['codigo' => 4, 'nombre_privilegio' => 'Administrar Usuarios'],
        ]);

        // DECANO
        DB::table('decano')->insert([
            ['id' => 1, 'id_persona' => 16, 'codigo' => 'DEC001', 'fecha_designacion' => '2020-01-01', 'titulo_profesional' => 'Doctor en Ciencias', 'sigla_facultad' => 'FICCT'],
        ]);

        // DOCENTE
        DB::table('docente')->insert([
            ['id' => 1, 'id_persona' => 11, 'codigo' => '1011', 'especialidad' => 'Matemáticas', 'profesional_area' => 'Ciencias Exactas', 'maestria' => 'Maestría en Educación', 'diplomado_educacion_superior' => 'Diplomado en Docencia', 'cantidad_grupos_asignados' => 2],
            ['id' => 2, 'id_persona' => 12, 'codigo' => '1012', 'especialidad' => 'Física', 'profesional_area' => 'Ciencias Exactas', 'maestria' => 'Maestría en Física', 'diplomado_educacion_superior' => 'Diplomado en Educación', 'cantidad_grupos_asignados' => 1],
            ['id' => 3, 'id_persona' => 13, 'codigo' => '1013', 'especialidad' => 'Computación', 'profesional_area' => 'Ingeniería', 'maestria' => 'Maestría en Informática', 'diplomado_educacion_superior' => 'Diplomado en Educación', 'cantidad_grupos_asignados' => 2],
            ['id' => 4, 'id_persona' => 14, 'codigo' => '1014', 'especialidad' => 'Inglés', 'profesional_area' => 'Idiomas', 'maestria' => 'Maestría en Lingüística', 'diplomado_educacion_superior' => 'Diplomado en Educación', 'cantidad_grupos_asignados' => 1],
        ]);

        // ADMINISTRATIVO
        DB::table('administrativo')->insert([
            ['id' => 1, 'id_persona' => 17, 'codigo' => 'ADM001', 'horario_trabajo' => '08:00-16:00'],
            ['id' => 2, 'id_persona' => 18, 'codigo' => 'ADM002', 'horario_trabajo' => '09:00-17:00'],
            ['id' => 3, 'id_persona' => 19, 'codigo' => 'ADM003', 'horario_trabajo' => '07:00-15:00'],
            ['id' => 4, 'id_persona' => 20, 'codigo' => 'ADM004', 'horario_trabajo' => '10:00-18:00'],
        ]);

        // COORDINADOR
        DB::table('coordinador')->insert([
            ['id' => 1, 'id_persona' => 21, 'codigo' => 'COO001', 'horario_trabajo' => '08:00-16:00'],
            ['id' => 2, 'id_persona' => 22, 'codigo' => 'COO002', 'horario_trabajo' => '09:00-17:00'],
        ]);

        // CARGA_HORARIA_DOCENTE
        DB::table('carga_horaria_docente')->insert([
            ['id' => 1, 'horas_asignadas' => 12, 'codigo_docente' => 1],
            ['id' => 2, 'horas_asignadas' => 8, 'codigo_docente' => 2],
            ['id' => 3, 'horas_asignadas' => 14, 'codigo_docente' => 3],
            ['id' => 4, 'horas_asignadas' => 6, 'codigo_docente' => 4],
        ]);

        // GRUPO
        DB::table('grupo')->insert([
            ['codigo' => 1, 'nombre_grupo' => 'G1-INF-2026', 'capacidad_maxima' => 70, 'codigo_materia' => 4, 'codigo_docente' => 3, 'codigo_horario' => 1],
            ['codigo' => 2, 'nombre_grupo' => 'G2-MAT-2026', 'capacidad_maxima' => 70, 'codigo_materia' => 2, 'codigo_docente' => 1, 'codigo_horario' => 2],
            ['codigo' => 3, 'nombre_grupo' => 'G3-FIS-2026', 'capacidad_maxima' => 70, 'codigo_materia' => 2, 'codigo_docente' => 2, 'codigo_horario' => 3],
            ['codigo' => 4, 'nombre_grupo' => 'G4-ING-2026', 'capacidad_maxima' => 70, 'codigo_materia' => 3, 'codigo_docente' => 4, 'codigo_horario' => 4],
        ]);

        // INSCRIPCION
        DB::table('inscripcion')->insert([
            ['fecha_inscripcion' => '2026-01-10', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-11', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-12', 'estado_pago' => 'Pendiente', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-13', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-14', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-15', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-16', 'estado_pago' => 'Pendiente', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-17', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-18', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-19', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-20', 'estado_pago' => 'Pendiente', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-21', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-22', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-23', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
            ['fecha_inscripcion' => '2026-01-24', 'estado_pago' => 'Pagado', 'codigo_gestion_academica' => 1, 'codigo_pago' => NULL, 'codigo_pasarelaPago' => NULL],
        ]);

        // PAGO
        DB::table('pago')->insert([
            ['monto' => 150.00, 'fecha_pago' => '2026-01-10', 'comprobante' => 'REC-0001'],
            ['monto' => 150.00, 'fecha_pago' => '2026-01-11', 'comprobante' => 'REC-0002'],
            ['monto' => 150.00, 'fecha_pago' => '2026-01-13', 'comprobante' => 'REC-0003'],
            ['monto' => 150.00, 'fecha_pago' => '2026-01-14', 'comprobante' => 'REC-0004'],
            ['monto' => 150.00, 'fecha_pago' => '2026-01-15', 'comprobante' => 'REC-0005'],
        ]);

        // PASARELA_PAGO
        DB::table('pasarela_pago')->insert([
            ['monto' => 150.00, 'fecha_pago' => '2026-01-10', 'comprobante' => 'TP-0001', 'codigo_pago' => 1],
            ['monto' => 150.00, 'fecha_pago' => '2026-01-11', 'comprobante' => 'TP-0002', 'codigo_pago' => 2],
            ['monto' => 150.00, 'fecha_pago' => '2026-01-13', 'comprobante' => 'TP-0003', 'codigo_pago' => 3],
        ]);

        // POSTULANTE
        DB::table('postulante')->insert([
            ['id_persona' => 1, 'registro' => 'P001', 'colegio_procedencia' => 'Colegio Nacional', 'ciudad' => 'Santa Cruz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 1, 'codigo_grupo' => 1, 'carrera_primera_opcion_id' => 1, 'carrera_segunda_opcion_id' => 2, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 2, 'registro' => 'P002', 'colegio_procedencia' => 'Colegio Alemán', 'ciudad' => 'La Paz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 2, 'codigo_grupo' => 1, 'carrera_primera_opcion_id' => 2, 'carrera_segunda_opcion_id' => 3, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 3, 'registro' => 'P003', 'colegio_procedencia' => 'Colegio San Antonio', 'ciudad' => 'Cochabamba', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 3, 'codigo_grupo' => 2, 'carrera_primera_opcion_id' => 3, 'carrera_segunda_opcion_id' => 4, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 4, 'registro' => 'P004', 'colegio_procedencia' => 'Colegio Cristo Rey', 'ciudad' => 'Santa Cruz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 4, 'codigo_grupo' => 2, 'carrera_primera_opcion_id' => 4, 'carrera_segunda_opcion_id' => 1, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 5, 'registro' => 'P005', 'colegio_procedencia' => 'Colegio San José', 'ciudad' => 'Santa Cruz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 5, 'codigo_grupo' => 3, 'carrera_primera_opcion_id' => 1, 'carrera_segunda_opcion_id' => 3, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 6, 'registro' => 'P006', 'colegio_procedencia' => 'Colegio Santa María', 'ciudad' => 'La Paz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 6, 'codigo_grupo' => 3, 'carrera_primera_opcion_id' => 2, 'carrera_segunda_opcion_id' => 1, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 7, 'registro' => 'P007', 'colegio_procedencia' => 'Colegio Don Bosco', 'ciudad' => 'Cochabamba', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 7, 'codigo_grupo' => 4, 'carrera_primera_opcion_id' => 3, 'carrera_segunda_opcion_id' => 2, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 8, 'registro' => 'P008', 'colegio_procedencia' => 'Colegio San Miguel', 'ciudad' => 'Santa Cruz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 8, 'codigo_grupo' => 4, 'carrera_primera_opcion_id' => 4, 'carrera_segunda_opcion_id' => 1, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 9, 'registro' => 'P009', 'colegio_procedencia' => 'Colegio La Salle', 'ciudad' => 'La Paz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 9, 'codigo_grupo' => 1, 'carrera_primera_opcion_id' => 1, 'carrera_segunda_opcion_id' => 4, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 10, 'registro' => 'P010', 'colegio_procedencia' => 'Colegio Nacional 2', 'ciudad' => 'Santa Cruz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 10, 'codigo_grupo' => 2, 'carrera_primera_opcion_id' => 2, 'carrera_segunda_opcion_id' => 3, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 11, 'registro' => 'P011', 'colegio_procedencia' => 'Colegio Central', 'ciudad' => 'Cochabamba', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 11, 'codigo_grupo' => 3, 'carrera_primera_opcion_id' => 3, 'carrera_segunda_opcion_id' => 1, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 12, 'registro' => 'P012', 'colegio_procedencia' => 'Colegio Libertad', 'ciudad' => 'La Paz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 12, 'codigo_grupo' => 4, 'carrera_primera_opcion_id' => 4, 'carrera_segunda_opcion_id' => 2, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 13, 'registro' => 'P013', 'colegio_procedencia' => 'Colegio Nuevo', 'ciudad' => 'Santa Cruz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 13, 'codigo_grupo' => 1, 'carrera_primera_opcion_id' => 1, 'carrera_segunda_opcion_id' => 2, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 14, 'registro' => 'P014', 'colegio_procedencia' => 'Colegio San Pedro', 'ciudad' => 'Cochabamba', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 14, 'codigo_grupo' => 2, 'carrera_primera_opcion_id' => 2, 'carrera_segunda_opcion_id' => 4, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
            ['id_persona' => 15, 'registro' => 'P015', 'colegio_procedencia' => 'Colegio Santa Ana', 'ciudad' => 'La Paz', 'titulo_bachiller' => 'Sí', 'otros_requisitos' => 'Ninguno', 'codigo_inscripcion' => 15, 'codigo_grupo' => 3, 'carrera_primera_opcion_id' => 3, 'carrera_segunda_opcion_id' => 1, 'carrera_asignada_id' => null, 'estado_asignacion' => 'Pendiente'],
        ]);

        // EXAMEN
        DB::table('examen')->insert([
            ['codigo' => 1, 'fecha_examen' => '2026-02-10', 'registro_postulante' => 1],
            ['codigo' => 2, 'fecha_examen' => '2026-02-10', 'registro_postulante' => 2],
            ['codigo' => 3, 'fecha_examen' => '2026-02-11', 'registro_postulante' => 3],
            ['codigo' => 4, 'fecha_examen' => '2026-02-11', 'registro_postulante' => 4],
            ['codigo' => 5, 'fecha_examen' => '2026-02-12', 'registro_postulante' => 5],
            ['codigo' => 6, 'fecha_examen' => '2026-02-12', 'registro_postulante' => 6],
            ['codigo' => 7, 'fecha_examen' => '2026-02-13', 'registro_postulante' => 7],
            ['codigo' => 8, 'fecha_examen' => '2026-02-13', 'registro_postulante' => 8],
            ['codigo' => 9, 'fecha_examen' => '2026-02-14', 'registro_postulante' => 9],
            ['codigo' => 10, 'fecha_examen' => '2026-02-14', 'registro_postulante' => 10],
            ['codigo' => 11, 'fecha_examen' => '2026-02-15', 'registro_postulante' => 11],
            ['codigo' => 12, 'fecha_examen' => '2026-02-15', 'registro_postulante' => 12],
            ['codigo' => 13, 'fecha_examen' => '2026-02-16', 'registro_postulante' => 13],
            ['codigo' => 14, 'fecha_examen' => '2026-02-16', 'registro_postulante' => 14],
            ['codigo' => 15, 'fecha_examen' => '2026-02-17', 'registro_postulante' => 15],
        ]);

        // CONFIGURACION_PORCENTAJES
        DB::table('configuracion_porcentajes')->insert([
            ['codigo' => 1, 'porcentaje_examen1' => 30, 'porcentaje_examen2' => 30, 'porcentaje_examen3' => 40, 'codigo_examen' => NULL],
        ]);

        // CALIFICACION
        $calificaciones = [
            ['id' => 1, 'nota1' => 78, 'nota2' => 78, 'nota3' => 78, 'registro_postulante' => 1, 'codigo_examen' => 1],
            ['id' => 2, 'nota1' => 85, 'nota2' => 85, 'nota3' => 85, 'registro_postulante' => 2, 'codigo_examen' => 2],
            ['id' => 3, 'nota1' => 62, 'nota2' => 62, 'nota3' => 62, 'registro_postulante' => 3, 'codigo_examen' => 3],
            ['id' => 4, 'nota1' => 55, 'nota2' => 55, 'nota3' => 55, 'registro_postulante' => 4, 'codigo_examen' => 4],
            ['id' => 5, 'nota1' => 90, 'nota2' => 90, 'nota3' => 90, 'registro_postulante' => 5, 'codigo_examen' => 5],
            ['id' => 6, 'nota1' => 47, 'nota2' => 47, 'nota3' => 47, 'registro_postulante' => 6, 'codigo_examen' => 6],
            ['id' => 7, 'nota1' => 73, 'nota2' => 73, 'nota3' => 73, 'registro_postulante' => 7, 'codigo_examen' => 7],
            ['id' => 8, 'nota1' => 66, 'nota2' => 66, 'nota3' => 66, 'registro_postulante' => 8, 'codigo_examen' => 8],
            ['id' => 9, 'nota1' => 81, 'nota2' => 81, 'nota3' => 81, 'registro_postulante' => 9, 'codigo_examen' => 9],
            ['id' => 10, 'nota1' => 59, 'nota2' => 59, 'nota3' => 59, 'registro_postulante' => 10, 'codigo_examen' => 10],
            ['id' => 11, 'nota1' => 77, 'nota2' => 77, 'nota3' => 77, 'registro_postulante' => 11, 'codigo_examen' => 11],
            ['id' => 12, 'nota1' => 88, 'nota2' => 88, 'nota3' => 88, 'registro_postulante' => 12, 'codigo_examen' => 12],
            ['id' => 13, 'nota1' => 69, 'nota2' => 69, 'nota3' => 69, 'registro_postulante' => 13, 'codigo_examen' => 13],
            ['id' => 14, 'nota1' => 92, 'nota2' => 92, 'nota3' => 92, 'registro_postulante' => 14, 'codigo_examen' => 14],
            ['id' => 15, 'nota1' => 54, 'nota2' => 54, 'nota3' => 54, 'registro_postulante' => 15, 'codigo_examen' => 15],
        ];
        
        foreach ($calificaciones as $cal) {
            $promedio = ($cal['nota1'] + $cal['nota2'] + $cal['nota3']) / 3;
            $estado = $promedio >= 60 ? 'APROBADO' : 'REPROBADO';
            DB::table('calificacion')->insert([
                'id' => $cal['id'],
                'nota1' => $cal['nota1'],
                'nota2' => $cal['nota2'],
                'nota3' => $cal['nota3'],
                'promedio' => $promedio,
                'estado' => $estado,
                'registro_postulante' => $cal['registro_postulante'],
                'codigo_examen' => $cal['codigo_examen'],
            ]);
        }

        // DOCUMENTOS
        DB::table('documentos')->insert([
            ['codigo' => 1, 'tipo_documento' => 'Título Bachiller', 'codigo_docente' => NULL, 'registro_postulante' => 1],
            ['codigo' => 2, 'tipo_documento' => 'Certificado de Estudios', 'codigo_docente' => NULL, 'registro_postulante' => 2],
            ['codigo' => 3, 'tipo_documento' => 'Título Profesional', 'codigo_docente' => 1, 'registro_postulante' => NULL],
            ['codigo' => 4, 'tipo_documento' => 'Diploma', 'codigo_docente' => 3, 'registro_postulante' => NULL],
        ]);

        // CONTROL_ASIGNACION_CARRERA
        DB::table('control_asignacion_carrera')->insert([
            ['codigo' => 1, 'postulante_id' => 1, 'carrera_asignada_id' => 1, 'fecha_asignacion' => '2026-03-01', 'es_segunda_opcion' => false, 'prioridad' => 1, 'observacion' => 'Asignado a primera opción'],
            ['codigo' => 2, 'postulante_id' => 2, 'carrera_asignada_id' => 2, 'fecha_asignacion' => '2026-03-01', 'es_segunda_opcion' => false, 'prioridad' => 1, 'observacion' => 'Asignado a primera opción'],
            ['codigo' => 3, 'postulante_id' => 3, 'carrera_asignada_id' => 3, 'fecha_asignacion' => '2026-03-02', 'es_segunda_opcion' => false, 'prioridad' => 1, 'observacion' => 'Asignado a primera opción'],
        ]);

        // REPORTE
        DB::table('reporte')->insert([
            ['codigo' => 1, 'tipo_reporte' => 'Lista Postulantes', 'fecha_generacion' => '2026-03-05', 'formato' => 'PDF', 'id_persona' => 1],
            ['codigo' => 2, 'tipo_reporte' => 'Postulantes Aprobados', 'fecha_generacion' => '2026-03-06', 'formato' => 'Excel', 'id_persona' => 2],
        ]);

        // BITACORA
        DB::table('bitacora')->insert([
            ['codigo' => 1, 'accion' => 'Creación de postulante P001', 'fecha_hora' => '2026-01-10 09:00:00', 'ip_origen' => '192.168.1.10', 'id_persona' => 1],
            ['codigo' => 2, 'accion' => 'Registro de pago P002', 'fecha_hora' => '2026-01-11 10:15:00', 'ip_origen' => '192.168.1.11', 'id_persona' => 2],
        ]);

        // LOTE_USUARIOS
        DB::table('lote_usuarios')->insert([
            ['codigo' => 1, 'archivo_csv' => 'lote_usuarios_enero.csv', 'fecha_carga' => '2026-01-05', 'usuario_carga' => 'admin', 'id_persona' => 1],
        ]);

        // ESTADISTICA
        DB::table('estadistica')->insert([
            ['codigo' => 1, 'total_inscritos' => 15, 'total_aprobados' => 9, 'total_reprobados' => 6, 'total_grupos_habilitados' => 1],
        ]);

        // CONFIGURACION_PORCENTAJES additional
        DB::table('configuracion_porcentajes')->insert([
            ['codigo' => 2, 'porcentaje_examen1' => 30, 'porcentaje_examen2' => 30, 'porcentaje_examen3' => 40, 'codigo_examen' => NULL],
        ]);

        // ROL_GRUPO_PRIVILEGIO - Asignar CUs a grupos
        DB::table('rol_grupo_privilegio')->insert([
            // Docente
            ['codigo_rol_grupo' => 1, 'codigo_cu' => 'CU05', 'descripcion_cu' => 'Registrar Calificaciones por Materia'],
            // Administrativo
            ['codigo_rol_grupo' => 2, 'codigo_cu' => 'CU02', 'descripcion_cu' => 'Gestionar Postulantes'],
            ['codigo_rol_grupo' => 2, 'codigo_cu' => 'CU03', 'descripcion_cu' => 'Generar Reportes'],
            ['codigo_rol_grupo' => 2, 'codigo_cu' => 'CU05', 'descripcion_cu' => 'Registrar Calificaciones por Materia'],
            // Coordinador
            ['codigo_rol_grupo' => 3, 'codigo_cu' => 'CU02', 'descripcion_cu' => 'Gestionar Postulantes'],
            ['codigo_rol_grupo' => 3, 'codigo_cu' => 'CU03', 'descripcion_cu' => 'Generar Reportes'],
            ['codigo_rol_grupo' => 3, 'codigo_cu' => 'CU05', 'descripcion_cu' => 'Registrar Calificaciones por Materia'],
        ]);
    }
}
