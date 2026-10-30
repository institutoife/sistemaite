<?php
namespace Tests;
use App\Models\Prospecto;
abstract class CrmTestCase extends GoogleContactsTestCase
{
    use CrmFixtures;
    protected $operator;
    protected function setUp(): void
    {
        parent::setUp(); $this->operator=$this->actor(); $this->prepareCrmFixtures();
        foreach (['Crear Personas','Listar Personas','Editar Personas'] as $name) { $this->operator->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web'])); }
        $this->actingAs($this->operator);
        \Illuminate\Support\Facades\Storage::fake('local'); \Illuminate\Support\Facades\Storage::fake('public');
    }
    protected function lead(array $data=[]): Prospecto
    {
        $record=new Prospecto;
        $record->forceFill(array_merge(['telefono'=>'70000001','estado_id'=>2,'crm_etapa_id'=>app(\App\Services\CrmService::class)->initial()->id,
            'etapa_desde'=>now(),'crm_status'=>'activo','responsable_id'=>$this->operator->id,'creado_por'=>$this->operator->id,
            'responsable_inicial_id'=>$this->operator->id,'crm_services_migrated_at'=>now(),'gcontact_sync_pending'=>false,'gcontact_sync_status'=>'idle'],$data));
        Prospecto::withoutEvents(function () use ($record) { $record->save(); });
        if ($record->interest_id) { $record->interests()->sync([$record->interest_id]); }
        return $record;
    }
}
