<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

  /* HEADER */
  .header { background: linear-gradient(135deg, #0e7490, #0c4a6e); color: #fff; padding: 20px 30px; }
  .header-inner { display: flex; justify-content: space-between; align-items: flex-start; }
  .header h1 { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
  .header .subtitle { font-size: 11px; opacity: 0.85; margin-top: 4px; }
  .badge { display: inline-block; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4);
           padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; margin-top: 6px; }

  /* META STRIP */
  .meta { background: #f1f5f9; border-bottom: 2px solid #e2e8f0; padding: 10px 30px; }
  .meta-grid { display: flex; gap: 30px; }
  .meta-item label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
  .meta-item p { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 1px; }

  /* BODY */
  .content { padding: 20px 30px; }
  .section-title { font-size: 13px; font-weight: bold; color: #0e7490; border-bottom: 2px solid #0e7490;
                   padding-bottom: 5px; margin-bottom: 12px; margin-top: 16px; }

  /* TABLE */
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  thead tr { background: #0e7490; color: #fff; }
  thead th { padding: 7px 8px; text-align: left; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody tr:nth-child(odd)  { background: #ffffff; }
  tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  .text-center { text-align: center; }
  .text-right  { text-align: right; }

  /* BADGES */
  .badge-aprobado  { background: #dcfce7; color: #166534; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
  .badge-reprobado { background: #fee2e2; color: #991b1b; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
  .badge-generado  { background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
  .badge-pendiente { background: #fef9c3; color: #854d0e; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }

  /* STATS ROW */
  .stats { display: flex; gap: 12px; margin-bottom: 16px; }
  .stat-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; text-align: center; }
  .stat-card .num { font-size: 22px; font-weight: bold; color: #0e7490; }
  .stat-card .lbl { font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-top: 2px; }

  /* FOOTER */
  .footer { margin-top: 30px; padding: 12px 30px; border-top: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; }
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
  <div class="header-inner">
    <div>
      <h1>Sistema de Gestión Académica — CUP</h1>
      <p class="subtitle">Reporte Oficial · {{ $tipo_reporte }}</p>
      <span class="badge">{{ strtoupper($formato) }}</span>
    </div>
    <div style="text-align:right;">
      <p style="font-size:11px; opacity:0.9;">{{ now()->format('d/m/Y H:i') }}</p>
      <p style="font-size:10px; opacity:0.7; margin-top:4px;">Generado por: {{ $generado_por }}</p>
    </div>
  </div>
</div>

<!-- META -->
<div class="meta">
  <div class="meta-grid">
    <div class="meta-item"><label>Tipo de Reporte</label><p>{{ $tipo_reporte }}</p></div>
    <div class="meta-item"><label>Formato</label><p>{{ $formato }}</p></div>
    <div class="meta-item"><label>Fecha</label><p>{{ now()->format('d/m/Y') }}</p></div>
    <div class="meta-item"><label>Total Registros</label><p>{{ $total_registros }}</p></div>
    @if($descripcion)
    <div class="meta-item"><label>Descripción</label><p>{{ $descripcion }}</p></div>
    @endif
  </div>
</div>

<div class="content">

  <!-- CALIFICACIONES -->
  @if($tipo_reporte === 'Calificaciones')
  @php
    $aprobados  = collect($datos)->where('promedio_materia', '>=', 60)->count();
    $reprobados = collect($datos)->where('promedio_materia', '<', 60)->count();
    $promGeneral = collect($datos)->avg('promedio_materia');
  @endphp
  <div class="stats">
    <div class="stat-card"><div class="num">{{ $total_registros }}</div><div class="lbl">Calificaciones</div></div>
    <div class="stat-card"><div class="num" style="color:#16a34a;">{{ $aprobados }}</div><div class="lbl">Aprobados</div></div>
    <div class="stat-card"><div class="num" style="color:#dc2626;">{{ $reprobados }}</div><div class="lbl">Reprobados</div></div>
    <div class="stat-card"><div class="num">{{ $promGeneral ? round($promGeneral,1) : '—' }}</div><div class="lbl">Prom. General</div></div>
  </div>
  <p class="section-title">Detalle de Calificaciones</p>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Registro</th>
        <th>CI</th>
        <th>Nombre</th>
        <th>Materia</th>
        <th class="text-center">N1</th>
        <th class="text-center">N2</th>
        <th class="text-center">N3</th>
        <th class="text-center">Promedio</th>
        <th class="text-center">Estado</th>
      </tr>
    </thead>
    <tbody>
      @foreach($datos as $i => $row)
      <tr>
        <td class="text-center" style="color:#94a3b8;">{{ $i + 1 }}</td>
        <td style="font-family:monospace; color:#1d4ed8; font-weight:bold;">{{ $row->registro }}</td>
        <td>{{ $row->ci }}</td>
        <td>{{ $row->nombre_completo }}</td>
        <td style="font-weight:bold;">{{ $row->sigla }}</td>
        <td class="text-center">{{ $row->nota1 ?? '—' }}</td>
        <td class="text-center">{{ $row->nota2 ?? '—' }}</td>
        <td class="text-center">{{ $row->nota3 ?? '—' }}</td>
        <td class="text-center" style="font-weight:bold;">{{ $row->promedio_materia }}</td>
        <td class="text-center">
          @if($row->promedio_materia >= 60)
            <span class="badge-aprobado">APROBADO</span>
          @else
            <span class="badge-reprobado">REPROBADO</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- POSTULANTES -->
  @elseif($tipo_reporte === 'Postulantes')
  @php
    $asignados = collect($datos)->where('estado_asignacion','Asignado')->count();
    $sinCupo   = collect($datos)->where('estado_asignacion','Sin cupo')->count();
    $pendientes = collect($datos)->whereNotIn('estado_asignacion',['Asignado','Sin cupo','Eliminado'])->count();
  @endphp
  <div class="stats">
    <div class="stat-card"><div class="num">{{ $total_registros }}</div><div class="lbl">Total</div></div>
    <div class="stat-card"><div class="num" style="color:#16a34a;">{{ $asignados }}</div><div class="lbl">Asignados</div></div>
    <div class="stat-card"><div class="num" style="color:#dc2626;">{{ $sinCupo }}</div><div class="lbl">Sin Cupo</div></div>
    <div class="stat-card"><div class="num" style="color:#d97706;">{{ $pendientes }}</div><div class="lbl">Pendientes</div></div>
  </div>
  <p class="section-title">Listado de Postulantes</p>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Registro</th>
        <th>CI</th>
        <th>Nombre</th>
        <th>1ra Opción</th>
        <th>2da Opción</th>
        <th class="text-center">Estado</th>
      </tr>
    </thead>
    <tbody>
      @foreach($datos as $i => $row)
      <tr>
        <td class="text-center" style="color:#94a3b8;">{{ $i + 1 }}</td>
        <td style="font-family:monospace; color:#1d4ed8; font-weight:bold;">{{ $row->registro }}</td>
        <td>{{ $row->ci }}</td>
        <td>{{ $row->nombre_completo }}</td>
        <td>{{ $row->primera_opcion ?? '—' }}</td>
        <td>{{ $row->segunda_opcion ?? '—' }}</td>
        <td class="text-center">
          @if($row->estado_asignacion === 'Asignado')
            <span class="badge-aprobado">{{ $row->estado_asignacion }}</span>
          @elseif($row->estado_asignacion === 'Sin cupo')
            <span class="badge-reprobado">{{ $row->estado_asignacion }}</span>
          @else
            <span class="badge-pendiente">{{ $row->estado_asignacion }}</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- GRUPOS -->
  @elseif($tipo_reporte === 'Grupos')
  <p class="section-title">Listado de Grupos</p>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Código</th>
        <th>Nombre Grupo</th>
        <th>Materia</th>
        <th>Sigla</th>
        <th>Docente</th>
      </tr>
    </thead>
    <tbody>
      @foreach($datos as $i => $row)
      <tr>
        <td class="text-center" style="color:#94a3b8;">{{ $i + 1 }}</td>
        <td style="font-family:monospace;">{{ $row->codigo }}</td>
        <td>{{ $row->nombre_grupo }}</td>
        <td>{{ $row->nombre_materia }}</td>
        <td style="font-weight:bold;">{{ $row->sigla }}</td>
        <td>{{ $row->docente ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- ASISTENCIA -->
  @elseif($tipo_reporte === 'Asistencia')
  @php
    $presentes    = collect($datos)->where('estado','Presente')->count();
    $ausentes     = collect($datos)->where('estado','Ausente')->count();
    $justificados = collect($datos)->where('estado','Justificado')->count();
  @endphp
  <div class="stats">
    <div class="stat-card"><div class="num">{{ $total_registros }}</div><div class="lbl">Total</div></div>
    <div class="stat-card"><div class="num" style="color:#16a34a;">{{ $presentes }}</div><div class="lbl">Presentes</div></div>
    <div class="stat-card"><div class="num" style="color:#dc2626;">{{ $ausentes }}</div><div class="lbl">Ausentes</div></div>
    <div class="stat-card"><div class="num" style="color:#d97706;">{{ $justificados }}</div><div class="lbl">Justificados</div></div>
  </div>
  <p class="section-title">Registro de Asistencia</p>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Fecha</th>
        <th>Registro</th>
        <th>Postulante</th>
        <th>Docente</th>
        <th class="text-center">Estado</th>
      </tr>
    </thead>
    <tbody>
      @foreach($datos as $i => $row)
      <tr>
        <td class="text-center" style="color:#94a3b8;">{{ $i + 1 }}</td>
        <td style="font-family:monospace;">{{ \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
        <td style="font-family:monospace; color:#1d4ed8; font-weight:bold;">{{ $row->registro }}</td>
        <td>{{ $row->postulante_nombre }}</td>
        <td>{{ $row->docente_nombre }}</td>
        <td class="text-center">
          @if($row->estado === 'Presente')
            <span class="badge-aprobado">Presente</span>
          @elseif($row->estado === 'Ausente')
            <span class="badge-reprobado">Ausente</span>
          @else
            <span class="badge-pendiente">Justificado</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

</div>

<!-- FOOTER -->
<div class="footer">
  <span>Sistema de Gestión Académica — CUP · {{ now()->format('Y') }}</span>
  <span>Reporte generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</span>
</div>

</body>
</html>
