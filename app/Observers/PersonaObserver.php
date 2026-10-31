<?php

namespace App\Observers;

use App\Models\Persona;
use App\Services\GoogleContactsService;

class PersonaObserver
{
    public function saving(Persona $persona): void
    {
        if ($persona->exists && \Illuminate\Support\Facades\Schema::hasColumn('prospectos','no_contactar')
            && \App\Models\Prospecto::where('persona_id',$persona->id)->where('no_contactar',true)->exists()) { $persona->votos=0; }
    }
    public function saved(Persona $persona): void
    {
        if ($persona->wasChanged('foto')) {
            \App\Models\User::where('persona_id',$persona->id)->update(['foto'=>$persona->foto]);
        }
        $commercialChange=($persona->wasRecentlyCreated||$persona->wasChanged('habilitado'))&&app(\App\Services\CrmService::class)->ready();
        if ($commercialChange) {
            $id=$persona->id; $actor=auth()->id();
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($id,$actor) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($id,$actor) {
                    if (Persona::whereKey($id)->lockForUpdate()->first(['id'])) { app(\App\Services\CrmService::class)->prepareLegacy($id,$actor); }
                });
            });
        }
        if ($persona->wasChanged(['nombre','apellidop','apellidom','telefono'])&&\Illuminate\Support\Facades\Schema::hasColumn('prospectos','persona_id')) {
            \App\Models\Prospecto::where('persona_id',$persona->id)->where('crm_status','<>','convertido')->update([
                'nombre'=>trim($persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom),'telefono'=>(string)$persona->telefono,
                'telefono_normalizado'=>app(\App\Services\TelefonoService::class)->normalize($persona->telefono),
                'crm_version'=>\Illuminate\Support\Facades\DB::raw('crm_version + 1')]);
        }
        if (!$persona->wasRecentlyCreated && !$commercialChange && !$persona->wasChanged([
            'nombre', 'apellidop', 'apellidom', 'telefono', 'fechanacimiento', 'foto', 'papelinicial',
        ])) {
            return;
        }
        $service = app(GoogleContactsService::class);
        if ($persona->wasRecentlyCreated || $persona->wasChanged(['nombre', 'apellidop', 'apellidom'])) {
            $service->markPersonAndGuardians($persona->id, auth()->id());
        } else {
            $service->markPending($persona, auth()->id());
        }
    }
}
