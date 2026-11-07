<div class="modal-header" style="background:#375F7A;color:white">
    <h5 class="modal-title" id="modalMarcadoNormalLabel">Asistencia y clases adicionales: {{ $persona->nombre }} {{ $persona->apellidop }}</h5>
    <button type="button" class="close text-white attendance-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body" data-attendance-modal>
    @if(empty($tabs))
        <div class="alert alert-info">No hay inscripciones o matr&iacute;culas activas disponibles. No se pueden registrar clases sin una inscripci&oacute;n vigente y autorizada.</div>
    @else
        <p class="small text-muted mb-2">Solo se muestran inscripciones y matrículas vigentes y en curso. Primero aparecen las que tienen clase hoy.</p>
        <ul class="nav nav-tabs flex-nowrap mb-3" style="overflow-x:auto;overflow-y:hidden" role="tablist">
            @foreach($tabs as $i=>$tab)
                <li class="nav-item"><a class="nav-link text-nowrap {{ $i===0?'active':'' }}" href="#attendance-{{ $tab['canal'] }}-{{ $tab['enrollment']->id }}" data-toggle="tab" role="tab" aria-selected="{{ $i===0?'true':'false' }}">{{ $tab['canal']==='apoyo'?'Inscripción':'Matrícula' }} #{{ $tab['enrollment']->id }} <span class="badge {{ $tab['todayPrograms']->isNotEmpty()?'badge-success':'badge-light' }}">{{ $tab['todayPrograms']->count() }} hoy</span></a></li>
            @endforeach
        </ul>
        <div class="tab-content">
            @foreach($tabs as $i=>$tab)
                <div id="attendance-{{ $tab['canal'] }}-{{ $tab['enrollment']->id }}" class="tab-pane fade {{ $i===0?'show active':'' }}" role="tabpanel">
                    @include('clase.asistencia.panel',['refreshUrl'=>route('marcado.presente.tabs',$persona->id)])
                </div>
            @endforeach
        </div>
    @endif
</div>
