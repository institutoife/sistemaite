<?php
namespace App\Services;

use App\Models\{CrmEtapa,Prospecto};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmStageService
{
    public function archive(CrmEtapa $stage, array $data, int $actor): void
    {
        DB::transaction(function () use ($stage,$data,$actor) {
            $stages=CrmEtapa::orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $current=$stages->get($stage->id);
            abort_unless($current,404);
            abort_if((int)$current->version!==(int)$data['version'],409,'La etapa cambió. Actualice la página.');
            if ($current->inicial) { throw ValidationException::withMessages(['etapa'=>'Seleccione otra etapa inicial antes de eliminar esta.']); }
            if ($current->conversion&&$current->activa&&!$stages->contains(function ($row) use ($current) { return $row->id!==$current->id&&$row->conversion&&$row->activa; })) {
                throw ValidationException::withMessages(['etapa'=>'Configure otra etapa activa de conversión antes de eliminar esta.']);
            }
            $records=Prospecto::where('crm_etapa_id',$current->id);
            if ((clone $records)->exists()) {
                $target=$stages->get($data['destino_id']??0);
                if (!$target||!$target->activa||$target->id===$current->id) { throw ValidationException::withMessages(['destino_id'=>'Seleccione una etapa de destino activa para conservar las fichas.']); }
                $invalid=(clone $records)->where(function ($query) use ($target) {
                    $target->conversion?$query->whereNull('convertido_at'):$query->whereNotNull('convertido_at');
                })->exists();
                if ($invalid) { throw ValidationException::withMessages(['destino_id'=>'El destino debe conservar la condición de conversión de todas las fichas.']); }
                $records->orderBy('id')->lockForUpdate()->chunkById(100,function ($rows) use ($current,$target,$actor) {
                    foreach ($rows as $record) {
                        $record->crm_etapa_id=$target->id; $record->etapa_desde=now(); $record->crm_version++; $record->save();
                        app(CrmService::class)->log($record,$actor,'etapa','Etapa eliminada del tablero: '.$current->nombre.'. Trasladado a '.$target->nombre,
                            ['datos'=>['anterior'=>$current->id,'nueva'=>$target->id,'etapa_eliminada'=>$current->nombre]]);
                    }
                });
            }
            $current->version++; $current->save(); $current->delete();
        },3);
    }
}
