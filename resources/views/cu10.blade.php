<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CU10 - Asignar Postulantes</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f1f5f9; }
        .header { background: linear-gradient(90deg, #1d4ed8, #1e40af); color: white; padding: 24px; }
        .header h1 { margin: 0; font-size: 2rem; }
        .container { max-width: 1024px; margin: 24px auto; padding: 0 16px; }
        .card { background: white; border-radius: 1rem; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); padding: 24px; }
        .button { display: inline-flex; align-items: center; justify-content: center; background: white; border-radius: 0.75rem; color: #1d4ed8; font-weight: 700; padding: 10px 18px; text-decoration: none; border: 1px solid #dbeafe; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
            <div>
                <h1>CU10 - Asignar Postulantes a Grupos</h1>
            </div>
            <a href="javascript:history.back()" class="button">Volver</a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <form method="POST" action="{{ url('/cu10/asignar-postulantes') }}">
                @csrf
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label for="criterio" style="font-weight:700;">Criterio de asignación</label>
                    <select id="criterio" name="criterio" style="padding:8px;border-radius:8px;border:1px solid #e2e8f0;">
                        <option value="registro_asc">Registro (A → Z)</option>
                        <option value="fecha_inscripcion_asc">Fecha de inscripción (más antiguo primero)</option>
                    </select>
                    <button type="submit" style="margin-left:auto;background:#059669;color:#fff;padding:10px 16px;border-radius:8px;border:none;font-weight:700;">Ejecutar asignación</button>
                </div>
            </form>

            @if(session('message'))
                <div style="margin-top:16px;padding:12px;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:8px;color:#065f46;">
                    {{ session('message') }}
                </div>
            @endif

            @if(!empty($results))
                <h3 style="margin-top:18px;">Resumen</h3>
                <p>Total postulantes: {{ $results['summary']['total_postulantes'] }}</p>
                <p>Asignados: {{ $results['summary']['asignados'] }}</p>
                <p>Sin grupo: {{ $results['summary']['sin_grupo'] }}</p>

                <h3 style="margin-top:12px;">Grupos</h3>
                <table style="width:100%;border-collapse:collapse;margin-top:8px;">
                    <thead>
                        <tr style="background:#f8fafc;text-align:left;">
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Grupo</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Materia</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Capacidad</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Inscritos</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Cupo libre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['groups'] as $g)
                            <tr>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->nombre_grupo ?? $g['nombre_grupo'] }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->materia_sigla ?? $g['materia_sigla'] }} - {{ $g->nombre_materia ?? $g['nombre_materia'] }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->capacidad_maxima ?? $g['capacidad_maxima'] }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->inscritos ?? $g['inscritos'] }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->cupo_disponible ?? $g['cupo_disponible'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <h3 style="margin-top:12px;">Detalles</h3>
                <div style="max-height:240px;overflow:auto;border:1px solid #eef2f7;border-radius:8px;padding:8px;background:#fff;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;text-align:left;">
                                <th style="padding:6px;border-bottom:1px solid #e6edf3;">Registro</th>
                                <th style="padding:6px;border-bottom:1px solid #e6edf3;">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['details'] as $d)
                                <tr>
                                    <td style="padding:6px;border-bottom:1px solid #f1f5f9;">{{ $d['registro'] }}</td>
                                    <td style="padding:6px;border-bottom:1px solid #f1f5f9;">{{ $d['resultado'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(empty($results) && isset($groups))
                <h3 style="margin-top:18px;">Grupos actuales</h3>
                <table style="width:100%;border-collapse:collapse;margin-top:8px;">
                    <thead>
                        <tr style="background:#f8fafc;text-align:left;">
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Grupo</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Materia</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Capacidad</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Inscritos</th>
                            <th style="padding:8px;border-bottom:1px solid #e6edf3;">Cupo libre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $g)
                            <tr>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->nombre_grupo }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->materia_sigla }} - {{ $g->nombre_materia }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->capacidad_maxima }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->inscritos }}</td>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $g->cupo_disponible }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            
            @if(isset($postulantes) && count($postulantes) > 0)
                <h3 style="margin-top:18px;">Postulantes</h3>
                <div style="overflow:auto;margin-top:8px;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;text-align:left;">
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">Código</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">Registro</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">SIGLAGRUPO1</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">MATERIA1</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">SIGLAGRUPO2</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">MATERIA2</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">SIGLAGRUPO3</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">MATERIA3</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">SIGLAGRUPO4</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">MATERIA4</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">CAPACIDAD MÁXIMA</th>
                                <th style="padding:8px;border-bottom:1px solid #e6edf3;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($postulantes as $p)
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $p->id }}</td>
                                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $p->registro }}</td>
                                    @php
                                        // For each of the first 4 groups, show values only if postulante belongs to that group index
                                        $groupCodes = $groups->pluck('codigo')->values();
                                    @endphp
                                    @for($i=0;$i<4;$i++)
                                        @php
                                            $sigla = '-'; $materia = '-';
                                            if(isset($groupCodes[$i]) && $p->codigo_grupo == $groupCodes[$i]){
                                                // find group
                                                $g = $groups->firstWhere('codigo', $p->codigo_grupo);
                                                if($g){ $sigla = $g->materia_sigla ?? '-'; $materia = $g->nombre_materia ?? '-'; }
                                            }
                                        @endphp
                                        <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $sigla }}</td>
                                        <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $materia }}</td>
                                    @endfor
                                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $p->capacidad_maxima ?? '-' }}</td>
                                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">
                                        <button type="button" onclick="openEdit({{ $p->id }}, {{ $p->codigo_grupo ?? 'null' }})" style="margin-right:6px;background:#2563eb;color:#fff;padding:6px 10px;border-radius:6px;border:none;">Editar</button>
                                        <form method="POST" action="{{ url('/cu10/postulante/'.$p->id) }}" style="display:inline;" onsubmit="return confirm('Eliminar asignación de {{ $p->registro }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background:#dc2626;color:#fff;padding:6px 10px;border-radius:6px;border:none;">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Edit modal (simple) -->
            <div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;padding:20px;z-index:60;">
                <div style="background:white;border-radius:12px;max-width:520px;width:100%;padding:16px;">
                    <h3 id="modalTitle">Editar asignación</h3>
                    <form id="editForm" method="POST" action="">
                        @csrf
                        @method('PATCH')
                        <div style="margin-top:12px;">
                            <label for="grupo_select" style="font-weight:700;">Grupo asignado</label>
                            <select id="grupo_select" name="codigo_grupo" style="width:100%;padding:8px;border-radius:8px;border:1px solid #e2e8f0;margin-top:6px;">
                                <option value="">Sin grupo</option>
                                @foreach($groups as $g)
                                    @php
                                        $label = ($g->nombre_grupo ?? $g['nombre_grupo']) . ' - ' . ($g->materia_sigla ?? $g['materia_sigla']);
                                        $inscritos = $g->inscritos ?? ($g['inscritos'] ?? 0);
                                        $capacidad = $g->capacidad_maxima ?? ($g['capacidad_maxima'] ?? '-');
                                        $cupo = ($capacidad === '-' ? '-' : max(0, $capacidad - $inscritos));
                                        $full = ($capacidad !== '-' && $inscritos >= $capacidad) ? 1 : 0;
                                    @endphp
                                    <option value="{{ $g->codigo }}" data-full="{{ $full }}" data-inscritos="{{ $inscritos }}" data-capacidad="{{ $capacidad }}">{{ $label }} ({{ $inscritos }} / {{ $capacidad }}) @if($full) - LLENO @endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
                            <button type="button" onclick="closeEdit()" style="padding:8px 12px;border-radius:8px;border:1px solid #e6edf3;background:#fff;">Cancelar</button>
                            <button type="submit" style="padding:8px 12px;border-radius:8px;border:none;background:#2563eb;color:#fff;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openEdit(id, currentGroup){
                    const modal = document.getElementById('editModal');
                    const form = document.getElementById('editForm');
                    form.action = '/cu10/postulante/' + id;
                    const select = document.getElementById('grupo_select');
                    if(currentGroup === null || currentGroup === undefined){ select.value = ''; } else { select.value = String(currentGroup); }
                    // update helper text
                    updateGroupInfo();
                    modal.style.display = 'flex';
                }
                function closeEdit(){
                    document.getElementById('editModal').style.display = 'none';
                }

                // show capacity info and warn if selected group is full
                function updateGroupInfo(){
                    const select = document.getElementById('grupo_select');
                    const infoId = 'groupInfo';
                    let info = document.getElementById(infoId);
                    if(!info){
                        info = document.createElement('div');
                        info.id = infoId;
                        info.style.marginTop = '8px';
                        info.style.color = '#334155';
                        document.getElementById('editForm').insertBefore(info, document.getElementById('editForm').firstChild.nextSibling);
                    }
                    const opt = select.options[select.selectedIndex];
                    if(!opt) return;
                    const inscritos = opt.dataset.inscritos || '0';
                    const capacidad = opt.dataset.capacidad || '-';
                    const full = opt.dataset.full === '1';
                    info.innerText = 'Inscritos: ' + inscritos + ' / Capacidad: ' + capacidad + (full ? ' — ¡Este grupo está lleno!' : '');
                }

                // intercept submit to confirm when selecting a full group
                document.getElementById('editForm').addEventListener('submit', function(e){
                    const select = document.getElementById('grupo_select');
                    const opt = select.options[select.selectedIndex];
                    if(opt && opt.dataset.full === '1'){
                        const ok = confirm('El grupo seleccionado aparece lleno. ¿Deseas intentar asignar de todas formas? (Si el servidor lo impide, la acción fallará)');
                        if(!ok){ e.preventDefault(); return false; }
                    }
                });
                document.getElementById('grupo_select').addEventListener('change', updateGroupInfo);
            </script>
        </div>
    </div>
</body>
</html>
