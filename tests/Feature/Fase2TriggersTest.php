<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Pruebas de Fase 2 - Triggers, Funciones y PA
 * Valida que los triggers funcionen correctamente:
 * - Email único (USUARIO)
 * - CI no duplicado (PERSONA)
 * - Formato de permiso válido (ROL_GRUPO_PRIVILEGIO)
 * - Validación de asistencia
 */
class Fase2TriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ejecutar migraciones antes de cada prueba
        $this->artisan('migrate');
    }

    // =========================================================================
    // PRUEBAS: TRIGGER EMAIL ÚNICO (USUARIO - TABLE: users)
    // =========================================================================

    /**
     * @test
     * Verifica que se puede insertar un usuario con email único
     */
    public function test_usuario_insert_email_unico_exitoso()
    {
        $user = DB::table('users')->insertGetId([
            'name' => 'Juan García',
            'email' => 'juan@example.com',
            'password' => bcrypt('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertGreaterThan(0, $user);
        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'name' => 'Juan García'
        ]);
    }

    /**
     * @test
     * Verifica que NO se puede insertar dos usuarios con el mismo email (trigger fn_usuario_insert)
     */
    public function test_usuario_insert_email_duplicado_falla()
    {
        $this->expectException(\Exception::class);

        // Insertar primer usuario
        DB::table('users')->insert([
            'name' => 'Juan García',
            'email' => 'juan@example.com',
            'password' => bcrypt('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Intentar insertar segundo usuario con mismo email
        // Esto debe fallar porque el trigger fn_validar_email_unico lo rechaza
        DB::table('users')->insert([
            'name' => 'Juan García Pérez',
            'email' => 'juan@example.com', // Email duplicado
            'password' => bcrypt('password456'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @test
     * Verifica que se registra en bitácora al insertar usuario (trigger fn_usuario_insert)
     */
    public function test_usuario_insert_registra_bitacora()
    {
        DB::table('users')->insert([
            'name' => 'María López',
            'email' => 'maria@example.com',
            'password' => bcrypt('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verificar que se registró en bitácora
        $bitacora = DB::table('bitacora')
            ->where('tabla_modificada', 'users')
            ->where('operacion', 'INSERT')
            ->first();

        $this->assertNotNull($bitacora);
        $this->assertStringContainsString('maria@example.com', $bitacora->valor_nuevo);
    }

    // =========================================================================
    // PRUEBAS: TRIGGER CI NO DUPLICADO (PERSONA)
    // =========================================================================

    /**
     * @test
     * Verifica que se puede insertar una persona con CI único
     */
    public function test_persona_insert_ci_unico_exitoso()
    {
        $persona = DB::table('persona')->insertGetId([
            'ci' => '12345678',
            'nombre' => 'Juan',
            'apellido' => 'García',
            'fecha_nacimiento' => '1990-01-15',
            'sexo' => 'M',
            'direccion' => 'Calle Principal 123',
            'telefono' => '7112345678',
            'correo_electronico' => 'juan@example.com',
            'ciudad' => 'La Paz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertGreaterThan(0, $persona);
        $this->assertDatabaseHas('persona', [
            'ci' => '12345678',
            'nombre' => 'Juan'
        ]);
    }

    /**
     * @test
     * Verifica que NO se puede insertar dos personas con el mismo CI (trigger fn_persona_insert)
     */
    public function test_persona_insert_ci_duplicado_falla()
    {
        $this->expectException(\Exception::class);

        // Insertar primera persona
        DB::table('persona')->insert([
            'ci' => '87654321',
            'nombre' => 'María',
            'apellido' => 'López',
            'fecha_nacimiento' => '1992-05-20',
            'sexo' => 'F',
            'direccion' => 'Avenida Principal 456',
            'telefono' => '7198765432',
            'correo_electronico' => 'maria@example.com',
            'ciudad' => 'La Paz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Intentar insertar segunda persona con mismo CI
        // Esto debe fallar porque el trigger fn_validar_ci_no_duplicado lo rechaza
        DB::table('persona')->insert([
            'ci' => '87654321', // CI duplicado
            'nombre' => 'María',
            'apellido' => 'García',
            'fecha_nacimiento' => '1995-03-10',
            'sexo' => 'F',
            'direccion' => 'Calle Secundaria 789',
            'telefono' => '7187654321',
            'correo_electronico' => 'maria.garcia@example.com',
            'ciudad' => 'Cochabamba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @test
     * Verifica que se registra en bitácora al insertar persona (trigger fn_persona_insert)
     */
    public function test_persona_insert_registra_bitacora()
    {
        DB::table('persona')->insert([
            'ci' => '11111111',
            'nombre' => 'Carlos',
            'apellido' => 'Rodríguez',
            'fecha_nacimiento' => '1988-07-22',
            'sexo' => 'M',
            'direccion' => 'Calle Tercera 321',
            'telefono' => '7111111111',
            'correo_electronico' => 'carlos@example.com',
            'ciudad' => 'Santa Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verificar que se registró en bitácora
        $bitacora = DB::table('bitacora')
            ->where('tabla_modificada', 'persona')
            ->where('operacion', 'INSERT')
            ->first();

        $this->assertNotNull($bitacora);
        $this->assertStringContainsString('CI: 11111111', $bitacora->valor_nuevo);
    }

    // =========================================================================
    // PRUEBAS: TRIGGER PERMISO VÁLIDO (ROL_GRUPO_PRIVILEGIO)
    // =========================================================================

    /**
     * @test
     * Verifica que se puede asignar un permiso con código CU válido
     */
    public function test_rol_grupo_privilegio_insert_codigo_valido_exitoso()
    {
        // Crear rol_grupo primero
        $rolGrupo = DB::table('rol_grupo')->insertGetId([
            'nombre_grupo' => 'Docentes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insertar privilegio con código CU válido
        $privilegio = DB::table('rol_grupo_privilegio')->insertGetId([
            'codigo_rol_grupo' => $rolGrupo,
            'codigo_cu' => 'CU01',
            'descripcion_cu' => 'Gestionar Usuarios',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertGreaterThan(0, $privilegio);
        $this->assertDatabaseHas('rol_grupo_privilegio', [
            'codigo_rol_grupo' => $rolGrupo,
            'codigo_cu' => 'CU01'
        ]);
    }

    /**
     * @test
     * Verifica que NO se puede asignar un permiso con código CU inválido (trigger fn_rol_grupo_privilegio_insert)
     */
    public function test_rol_grupo_privilegio_insert_codigo_invalido_falla()
    {
        $this->expectException(\Exception::class);

        // Crear rol_grupo primero
        $rolGrupo = DB::table('rol_grupo')->insertGetId([
            'nombre_grupo' => 'Administrativos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Intentar insertar privilegio con código CU inválido
        // Debe fallar porque fn_validar_permiso_valido rechaza formatos no CU##
        DB::table('rol_grupo_privilegio')->insert([
            'codigo_rol_grupo' => $rolGrupo,
            'codigo_cu' => 'INVALID123', // Código inválido
            'descripcion_cu' => 'Permiso Inválido',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @test
     * Verifica que se registra en bitácora al asignar permiso (trigger fn_rol_grupo_privilegio_insert)
     */
    public function test_rol_grupo_privilegio_insert_registra_bitacora()
    {
        // Crear rol_grupo primero
        $rolGrupo = DB::table('rol_grupo')->insertGetId([
            'nombre_grupo' => 'Coordinadores',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insertar privilegio
        DB::table('rol_grupo_privilegio')->insert([
            'codigo_rol_grupo' => $rolGrupo,
            'codigo_cu' => 'CU05',
            'descripcion_cu' => 'Registrar Calificaciones',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verificar que se registró en bitácora
        $bitacora = DB::table('bitacora')
            ->where('tabla_modificada', 'rol_grupo_privilegio')
            ->where('operacion', 'INSERT')
            ->first();

        $this->assertNotNull($bitacora);
        $this->assertStringContainsString('CU05', $bitacora->valor_nuevo);
    }

    // =========================================================================
    // PRUEBAS: FUNCIONES DE VALIDACIÓN
    // =========================================================================

    /**
     * @test
     * Verifica que la función fn_validar_email_unico funciona correctamente
     */
    public function test_funcion_validar_email_unico()
    {
        // Insertar un usuario
        DB::table('users')->insert([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Validar que email existente retorna false (NO es único)
        $result = DB::select('SELECT fn_validar_email_unico(?) as es_unico', ['test@example.com']);
        $this->assertFalse($result[0]->es_unico);

        // Validar que email no existente retorna true (SÍ es único)
        $result = DB::select('SELECT fn_validar_email_unico(?) as es_unico', ['nuevo@example.com']);
        $this->assertTrue($result[0]->es_unico);
    }

    /**
     * @test
     * Verifica que la función fn_validar_ci_no_duplicado funciona correctamente
     */
    public function test_funcion_validar_ci_no_duplicado()
    {
        // Insertar una persona
        DB::table('persona')->insert([
            'ci' => '99999999',
            'nombre' => 'Test',
            'apellido' => 'Person',
            'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'M',
            'direccion' => 'Test Address',
            'telefono' => '7199999999',
            'correo_electronico' => 'test@example.com',
            'ciudad' => 'La Paz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Validar que CI existente retorna false (NO es único)
        $result = DB::select('SELECT fn_validar_ci_no_duplicado(?) as no_duplicado', ['99999999']);
        $this->assertFalse($result[0]->no_duplicado);

        // Validar que CI no existente retorna true (SÍ es único)
        $result = DB::select('SELECT fn_validar_ci_no_duplicado(?) as no_duplicado', ['88888888']);
        $this->assertTrue($result[0]->no_duplicado);
    }

    /**
     * @test
     * Verifica que la función fn_validar_permiso_valido funciona correctamente
     */
    public function test_funcion_validar_permiso_valido()
    {
        // Validar formato correcto
        $result = DB::select('SELECT fn_validar_permiso_valido(?) as es_valido', ['CU01']);
        $this->assertTrue($result[0]->es_valido);

        $result = DB::select('SELECT fn_validar_permiso_valido(?) as es_valido', ['CU99']);
        $this->assertTrue($result[0]->es_valido);

        // Validar formato incorrecto
        $result = DB::select('SELECT fn_validar_permiso_valido(?) as es_valido', ['INVALID']);
        $this->assertFalse($result[0]->es_valido);

        $result = DB::select('SELECT fn_validar_permiso_valido(?) as es_valido', ['CU1']);
        $this->assertFalse($result[0]->es_valido);

        $result = DB::select('SELECT fn_validar_permiso_valido(?) as es_valido', ['CU100']);
        $this->assertFalse($result[0]->es_valido);
    }

    // =========================================================================
    // PRUEBAS: ASISTENCIA
    // =========================================================================

    /**
     * @test
     * Verifica que se registra asistencia correctamente
     */
    public function test_asistencia_insert_registra_bitacora()
    {
        // Crear datos necesarios
        $persona = DB::table('persona')->insertGetId([
            'ci' => '44444444',
            'nombre' => 'Docente Test',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1985-01-01',
            'sexo' => 'M',
            'direccion' => 'Test Address',
            'telefono' => '7144444444',
            'correo_electronico' => 'docente@example.com',
            'ciudad' => 'La Paz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docente = DB::table('docente')->insertGetId([
            'codigo' => 'DOC001',
            'id_persona' => $persona,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $postulante = DB::table('postulante')->insertGetId([
            'id_persona' => $persona,
            'registro' => 'POST001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insertar asistencia
        DB::table('asistencia')->insert([
            'fecha' => now()->toDateString(),
            'codigo_docente' => $docente,
            'registro_postulante' => $postulante,
        ]);

        // Verificar que se registró en bitácora
        $bitacora = DB::table('bitacora')
            ->where('tabla_modificada', 'asistencia')
            ->where('operacion', 'INSERT')
            ->first();

        $this->assertNotNull($bitacora);
        $this->assertStringContainsString('Postulante:', $bitacora->valor_nuevo);
    }
}
