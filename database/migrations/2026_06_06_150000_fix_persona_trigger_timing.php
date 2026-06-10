<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar de BEFORE a AFTER para que NEW.id esté disponible
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_insert ON persona CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_update ON persona CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_delete ON persona CASCADE");

        DB::statement("
            CREATE TRIGGER trg_persona_insert
            AFTER INSERT ON persona
            FOR EACH ROW
            EXECUTE FUNCTION fn_persona_insert()
        ");

        DB::statement("
            CREATE TRIGGER trg_persona_update
            AFTER UPDATE ON persona
            FOR EACH ROW
            EXECUTE FUNCTION fn_persona_update()
        ");

        DB::statement("
            CREATE TRIGGER trg_persona_delete
            AFTER DELETE ON persona
            FOR EACH ROW
            EXECUTE FUNCTION fn_persona_delete()
        ");
    }

    public function down(): void
    {
        //
    }
};
