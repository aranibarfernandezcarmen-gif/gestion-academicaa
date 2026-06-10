<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CU16ReportesController extends Controller
{
    public function index()
    {
        $reportes = DB::table('reporte')
            ->join('persona', 'reporte.id_persona', '=', 'persona.id')
            ->select(
                'reporte.codigo',
                'reporte.tipo_reporte',
                'reporte.fecha_generacion',
                'reporte.formato',
                'reporte.estado',
                'reporte.descripcion',
                'reporte.cantidad_registros',
                DB::raw("persona.nombre || ' ' || persona.apellido as generado_por")
            )
            ->orderBy('reporte.fecha_generacion', 'desc')
            ->get();

        $estadisticas = [
            'total'     => $reportes->count(),
            'generado'  => $reportes->where('estado', 'generado')->count(),
            'enviado'   => $reportes->where('estado', 'enviado')->count(),
            'archivado' => $reportes->where('estado', 'archivado')->count(),
        ];

        return Inertia::render('CU16Reportes', [
            'reportes'     => $reportes,
            'estadisticas' => $estadisticas,
        ]);
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'tipo_reporte' => 'required|string|max:100',
            'formato'      => 'required|in:PDF,Excel,CSV',
            'descripcion'  => 'nullable|string',
            'id_persona'   => 'required|integer|exists:persona,id',
        ]);

        $tipo      = $request->tipo_reporte;
        $formato   = $request->formato;
        $persona   = DB::table('persona')->where('id', $request->id_persona)->first();
        $generadoPor = $persona ? $persona->nombre . ' ' . $persona->apellido : 'Sistema';

        $datos = $this->getDatos($tipo);
        $total = count($datos);

        // Registrar en historial
        DB::table('reporte')->insert([
            'tipo_reporte'       => $tipo,
            'fecha_generacion'   => date('Y-m-d'),
            'formato'            => $formato,
            'estado'             => 'generado',
            'descripcion'        => $request->descripcion,
            'id_persona'         => $request->id_persona,
            'cantidad_registros' => $total,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $filename = 'reporte_' . strtolower(str_replace(' ', '_', $tipo)) . '_' . date('Ymd_His');

        if ($formato === 'PDF') {
            return $this->exportarPDF($tipo, $datos, $total, $generadoPor, $request->descripcion, $filename);
        } elseif ($formato === 'Excel') {
            return $this->exportarExcel($tipo, $datos, $filename);
        } else {
            return $this->exportarCSV($tipo, $datos, $filename);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_reporte' => 'required|string|max:100',
            'formato'      => 'required|in:PDF,Excel,CSV',
            'descripcion'  => 'nullable|string',
            'id_persona'   => 'required|integer|exists:persona,id',
        ]);

        $cantidad = $this->contarRegistros($request->tipo_reporte);

        DB::table('reporte')->insert([
            'tipo_reporte'       => $request->tipo_reporte,
            'fecha_generacion'   => date('Y-m-d'),
            'formato'            => $request->formato,
            'estado'             => 'generado',
            'descripcion'        => $request->descripcion,
            'id_persona'         => $request->id_persona,
            'cantidad_registros' => $cantidad,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return response()->json(['message' => 'Reporte registrado.']);
    }

    public function destroy($codigo)
    {
        DB::table('reporte')->where('codigo', $codigo)->delete();
        return response()->json(['message' => 'Reporte eliminado.']);
    }

    // ---- Datos por tipo ----
    private function getDatos(string $tipo): \Illuminate\Support\Collection
    {
        return match($tipo) {
            'Calificaciones' => DB::table('calificacion')
                ->join('postulante', 'calificacion.registro_postulante', '=', 'postulante.id')
                ->join('persona', 'postulante.id_persona', '=', 'persona.id')
                ->join('grupo', 'calificacion.codigo_grupo', '=', 'grupo.codigo')
                ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                ->whereNotNull('calificacion.codigo_grupo')
                ->select(
                    'postulante.registro', 'persona.ci',
                    DB::raw("persona.nombre || ' ' || persona.apellido as nombre_completo"),
                    'materia.sigla', 'materia.nombre_materia',
                    'calificacion.nota1', 'calificacion.nota2', 'calificacion.nota3',
                    DB::raw("ROUND((calificacion.nota1 + calificacion.nota2 + calificacion.nota3)::numeric / 3, 2) as promedio_materia")
                )
                ->orderBy('materia.codigo')->orderBy('postulante.registro')->get(),

            'Postulantes' => DB::table('postulante')
                ->join('persona', 'postulante.id_persona', '=', 'persona.id')
                ->leftJoin('carrera as c1', 'postulante.carrera_primera_opcion_id', '=', 'c1.codigo')
                ->leftJoin('carrera as c2', 'postulante.carrera_segunda_opcion_id', '=', 'c2.codigo')
                ->where('postulante.estado_asignacion', '<>', 'Eliminado')
                ->select(
                    'postulante.registro', 'persona.ci',
                    DB::raw("persona.nombre || ' ' || persona.apellido as nombre_completo"),
                    'c1.sigla as primera_opcion', 'c2.sigla as segunda_opcion',
                    'postulante.estado_asignacion'
                )
                ->orderBy('postulante.registro')->get(),

            'Grupos' => DB::table('grupo')
                ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                ->leftJoin('docente', 'grupo.codigo_docente', '=', 'docente.id')
                ->leftJoin('persona as dp', 'docente.id_persona', '=', 'dp.id')
                ->select(
                    'grupo.codigo', 'grupo.nombre_grupo',
                    'materia.nombre_materia', 'materia.sigla',
                    DB::raw("COALESCE(dp.nombre || ' ' || dp.apellido, 'Sin docente') as docente")
                )
                ->orderBy('materia.codigo')->get(),

            'Asistencia' => DB::table('asistencia')
                ->join('docente', 'asistencia.codigo_docente', '=', 'docente.id')
                ->join('persona as dp', 'docente.id_persona', '=', 'dp.id')
                ->join('postulante', 'asistencia.registro_postulante', '=', 'postulante.id')
                ->join('persona as pp', 'postulante.id_persona', '=', 'pp.id')
                ->select(
                    'asistencia.fecha',
                    'postulante.registro',
                    DB::raw("pp.nombre || ' ' || pp.apellido as postulante_nombre"),
                    DB::raw("dp.nombre || ' ' || dp.apellido as docente_nombre"),
                    'asistencia.estado'
                )
                ->orderBy('asistencia.fecha', 'desc')->get(),

            default => collect(),
        };
    }

    private function contarRegistros(string $tipo): int
    {
        return match($tipo) {
            'Calificaciones' => DB::table('calificacion')->whereNotNull('codigo_grupo')->count(),
            'Postulantes'    => DB::table('postulante')->where('estado_asignacion', '<>', 'Eliminado')->count(),
            'Grupos'         => DB::table('grupo')->count(),
            'Asistencia'     => DB::table('asistencia')->count(),
            default          => 0,
        };
    }

    // ---- Exportar PDF ----
    private function exportarPDF(string $tipo, $datos, int $total, string $generadoPor, ?string $descripcion, string $filename)
    {
        $pdf = Pdf::loadView('reportes.plantilla', [
            'tipo_reporte'   => $tipo,
            'formato'        => 'PDF',
            'datos'          => $datos,
            'total_registros' => $total,
            'generado_por'   => $generadoPor,
            'descripcion'    => $descripcion,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename . '.pdf');
    }

    // ---- Exportar Excel ----
    private function exportarExcel(string $tipo, $datos, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($tipo);

        // Estilo cabecera
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0E7490']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $headers = $this->getHeaders($tipo);
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $sheet->getStyle('A1:' . chr(ord('A') + count($headers) - 1) . '1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($datos as $item) {
            $values = $this->getRowValues($tipo, $item);
            $col = 'A';
            foreach ($values as $v) {
                $sheet->setCellValue($col . $row, $v);
                $col++;
            }
            // Zebra
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':' . chr(ord('A') + count($headers) - 1) . $row)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8FAFC');
            }
            $row++;
        }

        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'rpt_') . '.xlsx';
        $writer->save($tmpFile);

        return response()->download($tmpFile, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ---- Exportar CSV ----
    private function exportarCSV(string $tipo, $datos, string $filename)
    {
        $headers = $this->getHeaders($tipo);
        $output  = implode(',', $headers) . "\n";

        foreach ($datos as $item) {
            $values = $this->getRowValues($tipo, $item);
            $escaped = array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $values);
            $output .= implode(',', $escaped) . "\n";
        }

        return response($output, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    private function getHeaders(string $tipo): array
    {
        return match($tipo) {
            'Calificaciones' => ['Registro','CI','Nombre','Materia','Sigla','Nota 1','Nota 2','Nota 3','Promedio'],
            'Postulantes'    => ['Registro','CI','Nombre','1ra Opción','2da Opción','Estado'],
            'Grupos'         => ['Código','Nombre Grupo','Materia','Sigla','Docente'],
            'Asistencia'     => ['Fecha','Registro','Postulante','Docente','Estado'],
            default          => [],
        };
    }

    private function getRowValues(string $tipo, $item): array
    {
        return match($tipo) {
            'Calificaciones' => [$item->registro, $item->ci, $item->nombre_completo, $item->nombre_materia, $item->sigla, $item->nota1, $item->nota2, $item->nota3, $item->promedio_materia],
            'Postulantes'    => [$item->registro, $item->ci, $item->nombre_completo, $item->primera_opcion ?? '—', $item->segunda_opcion ?? '—', $item->estado_asignacion],
            'Grupos'         => [$item->codigo, $item->nombre_grupo, $item->nombre_materia, $item->sigla, $item->docente],
            'Asistencia'     => [$item->fecha, $item->registro, $item->postulante_nombre, $item->docente_nombre, $item->estado],
            default          => [],
        };
    }
}
