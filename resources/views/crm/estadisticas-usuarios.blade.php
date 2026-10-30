<h2 class="h4">{{ $general?'Estadísticas por usuario':'Mis estadísticas' }}</h2>
<p>Del {{ $userStats['start']->format('d/m/Y') }} al {{ $userStats['end']->format('d/m/Y') }}. Las asignaciones y tareas muestran el estado actual; las acciones y conversiones conservan su autor y responsable de ese momento.</p>
<div class="crm-summary">
@foreach(['registered'=>'Nuevos registrados','activities'=>'Actividades realizadas','stage_changes'=>'Cambios de etapa','moved_leads'=>'Fichas distintas movidas','assigned'=>'Activos asignados','pending'=>'Seguimientos pendientes','overdue'=>'Seguimientos vencidos','conversions_done'=>'Conversiones registradas','conversions_owned'=>'Conversiones como responsable','deactivated'=>'Fichas dadas de baja'] as $key=>$label)
<div><strong>{{ $userStats['totals'][$key] }}</strong><span>{{ $label }}</span></div>
@endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm crm-user-stats"><thead><tr><th>Usuario</th><th>Nuevos creados</th><th>Actividades</th><th>Cambios de etapa</th><th>Fichas distintas movidas</th><th>Activos asignados</th><th>Pendientes</th><th>Vencidos</th><th>Conversiones registradas por el usuario</th><th>Conversiones como responsable</th><th>Fichas dadas de baja</th><th>Recibidos en el período</th><th>Tasa de conversión como responsable</th></tr></thead><tbody>
@forelse($userStats['rows'] as $row)
<tr><th scope="row">{{ $row['nombre'] }}</th>
@foreach(['registered','activities','stage_changes','moved_leads','assigned','pending','overdue','conversions_done','conversions_owned','deactivated','received'] as $key)<td>{{ $row[$key] }}</td>
@endforeach
<td>{{ $row['rate']===null?'Sin cohorte':$row['rate'].' %' }}<small class="d-block">{{ $row['won'] }} / {{ $row['received'] }}</small></td></tr>
@empty<tr><td colspan="13">Sin registros para este período.</td></tr>
@endforelse
</tbody></table></div></div>
<p class="small text-muted">La tasa usa fichas distintas recibidas por cada responsable en el período, mediante alta o reasignación antes de su conversión. Cuenta como éxito una inscripción confirmada a cargo de ese responsable hasta el cierre del período. Volver a mover o reasignar la misma ficha no aumenta esa cohorte. Las conversiones de fichas recibidas antes se muestran aparte en las conversiones del período. No se inventa un responsable histórico cuando falta el dato.</p>
<p class="small text-muted">El filtro Servicio utiliza el interés guardado en cada acción para las actividades históricas. Una ficha puede interesarse por varios servicios; filtrarla por cualquiera no duplica sus conteos.</p>
