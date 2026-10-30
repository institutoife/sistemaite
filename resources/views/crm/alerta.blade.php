
@can('Listar Prospectos')
@if(app(\App\Services\CrmService::class)->ready())
@php $crmPending=\App\Models\Prospecto::where('crm_status','activo')->where('proximo_contacto_at','<=',now())->where('responsable_id',auth()->id())->count();
@endphp
@if($crmPending)<div class="alert alert-info py-2" role="status"><a href="{{ route('crm.lista',['agenda'=>'vencidos','status'=>'activo','responsable_id'=>auth()->id()]) }}">CRM: {{ $crmPending }} actividades pendientes hasta este momento asignadas a usted.</a></div>
@endif
@endif
@endcan
