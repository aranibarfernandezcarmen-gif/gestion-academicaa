<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Arreglos de la auditoría (triggers/funciones que usan columnas/valores inexistentes):
 *  - fn_pago_update: usaba OLD/NEW.estado_pago (no existe; la columna es 'estado') y
 *    comparaba 'COMPLETADO' (el enum real es 'Completado'). Rompía cualquier UPDATE en pago.
 *  - fn_rol_grupo_privilegio_insert/delete: insertaban bitacora con id_persona=0 (viola la
 *    FK a persona). Rompía asignar/quitar privilegios (CU02). Se usa NULL.
 *  - fn_usuario_insert/update/delete: id_persona = COALESCE(users.id, 0) (viola FK). Se usa NULL.
 *  - calificacion.nota1/2/3 eran NOT NULL pero CU05 permite guardar notas parciales (nullable).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Notas nullable (permite guardar calificaciones parciales sin error 500)
        DB::statement('ALTER TABLE calificacion ALTER COLUMN nota1 DROP NOT NULL');
        DB::statement('ALTER TABLE calificacion ALTER COLUMN nota2 DROP NOT NULL');
        DB::statement('ALTER TABLE calificacion ALTER COLUMN nota3 DROP NOT NULL');

        // 2) fn_pago_update: estado (no estado_pago) y 'Completado' (no 'COMPLETADO')
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_pago_update()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.estado IS DISTINCT FROM NEW.estado THEN
                    IF NEW.estado = 'Completado' THEN
                        UPDATE postulante
                        SET estado_asignacion = 'Aceptado'
                        WHERE codigo_inscripcion IN (
                            SELECT id FROM inscripcion WHERE codigo_pago = NEW.id
                        );
                        INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                        VALUES ('[TRIGGER] Pago completado - Monto: ' || NEW.monto, NOW(), '0.0.0.0', NULL);
                    END IF;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // 3) fn_rol_grupo_privilegio_insert / delete: id_persona NULL en vez de 0
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_rol_grupo_privilegio_insert()
            RETURNS TRIGGER AS \$\$
            DECLARE count_rol_grupo INT;
            BEGIN
                IF NOT fn_validar_permiso_valido(NEW.codigo_cu) THEN
                    RAISE EXCEPTION 'Código de permiso % inválido (formato CU01-CU50)', NEW.codigo_cu;
                END IF;
                SELECT COUNT(*) INTO count_rol_grupo FROM rol_grupo WHERE codigo = NEW.codigo_rol_grupo;
                IF count_rol_grupo = 0 THEN
                    RAISE EXCEPTION 'Rol grupo % no existe', NEW.codigo_rol_grupo;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Privilegio asignado', NOW(), '0.0.0.0', NULL, 'rol_grupo_privilegio', 'INSERT', NEW.codigo::TEXT,
                        NULL, CONCAT('Rol: ', NEW.codigo_rol_grupo, ', CU: ', NEW.codigo_cu));
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_rol_grupo_privilegio_delete()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Privilegio eliminado', NOW(), '0.0.0.0', NULL, 'rol_grupo_privilegio', 'DELETE', OLD.codigo::TEXT,
                        CONCAT('Rol: ', OLD.codigo_rol_grupo, ', CU: ', OLD.codigo_cu), NULL);
                RETURN OLD;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // 4) fn_usuario_insert / update / delete: id_persona NULL (users.id no es FK a persona)
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_insert()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NOT fn_validar_email_unico(NEW.email) THEN
                    RAISE EXCEPTION 'Email % ya existe en el sistema', NEW.email;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario creado', NOW(), '0.0.0.0', NULL, 'users', 'INSERT', NEW.id::TEXT, NULL, NEW.email);
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_update()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.email != NEW.email AND NOT fn_validar_email_unico(NEW.email) THEN
                    RAISE EXCEPTION 'Email % ya existe en el sistema', NEW.email;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario actualizado', NOW(), '0.0.0.0', NULL, 'users', 'UPDATE', NEW.id::TEXT, OLD.email, NEW.email);
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_delete()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario eliminado', NOW(), '0.0.0.0', NULL, 'users', 'DELETE', OLD.id::TEXT, OLD.email, NULL);
                RETURN OLD;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        // No revertir (los triggers quedan corregidos).
    }
};
