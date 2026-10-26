<?php

namespace App\Observers;

use App\Models\Persona;
use App\Services\GoogleContactsService;

class PersonaObserver
{
    public function saved(Persona $persona): void
    {
        if (!$persona->wasRecentlyCreated && !$persona->wasChanged([
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
