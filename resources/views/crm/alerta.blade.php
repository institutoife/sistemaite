
@can('Listar Prospectos')
@if(app(\App\Services\CrmService::class)->ready())
@php $crmPending=\App\Models\Prospecto::where('crm_status','activo')->where('proximo_contacto_at','<=',now())->where(function($query){$query->where('responsable_id',auth()->id())->orWhereNull('responsable_id');})->count();
@endphp
@if($crmPending)<div class="alert alert-info py-2" role="status"><a href="{{ route('crm.lista',['agenda'=>'vencidos','status'=>'activo']) }}">CRM: {{ $crmPending }} actividades pendientes hasta este momento para usted o sin asignar.</a></div>
@endif
@endif
@endcan
