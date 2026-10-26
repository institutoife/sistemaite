<?php

namespace App\Observers;

use App\Models\Inscripcione;
use App\Models\Matriculacion;
use App\Models\Persona;
use App\Services\GoogleContactsService;
use Illuminate\Database\Eloquent\Model;

class GoogleContactServiceObserver
{
    public function saved(Model $record): void
    {
        if ($record instanceof Inscripcione || $record instanceof Matriculacion) {
            $fields = $record instanceof Inscripcione ? ['estudiante_id', 'modalidad_id'] : ['computacion_id', 'asignatura_id'];
            if ($record->wasRecentlyCreated || $record->wasChanged($fields)) {
                $this->contracts($record);
            }
            return;
        }
        $field = $record instanceof \App\Models\Modalidad ? 'modalidad' : 'asignatura';
        if ($record->wasChanged($field)) {
            $this->catalogue($record);
        }
    }

    public function deleted(Model $record): void
    {
        if ($record instanceof Inscripcione || $record instanceof Matriculacion) {
            $this->contracts($record);
        }
    }

    private function contracts(Model $record): void
    {
        $academic = $record instanceof Inscripcione;
        $key = $academic ? 'estudiante_id' : 'computacion_id';
        $class = $academic ? \App\Models\Estudiante::class : \App\Models\Computacion::class;
        foreach (array_unique(array_filter([$record->$key, $record->getRawOriginal($key)])) as $id) {
            if ($personId = $class::whereKey($id)->value('persona_id')) {
                app(GoogleContactsService::class)->markPersonAndGuardians((int) $personId, auth()->id());
            }
        }
    }

    private function catalogue(Model $record): void
    {
        $academic = $record instanceof \App\Models\Modalidad;
        $relation = $academic ? 'estudiante.inscripciones' : 'computacion.matriculaciones';
        $foreign = $academic ? 'modalidad_id' : 'asignatura_id';
        Persona::whereHas($relation, function ($query) use ($record, $foreign) {
            $query->where($foreign, $record->id);
        })->select('id')->chunkById(100, function ($people) {
            foreach ($people as $person) {
                app(GoogleContactsService::class)->markPersonAndGuardians($person->id, auth()->id());
            }
        });
    }
}
