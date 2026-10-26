<?php

namespace App\Observers;

use App\Models\Comentario;
use App\Models\Persona;
use App\Services\GoogleContactsService;
use Illuminate\Database\Eloquent\Model;

class GoogleLeadObserver
{
    public function saved(Model $record): void
    {
        if (!$record->wasRecentlyCreated && !$record->wasChanged(['nombre', 'telefono', 'persona_id'])) {
            return;
        }
        $service = app(GoogleContactsService::class);
        if ($record instanceof Comentario && $record->persona_id) {
            // Persona owns the contact after conversion. The service recovers the enquiry link
            // under the account lock, even if its API request was still in flight at conversion.
            if ($persona = Persona::find($record->persona_id)) {
                $service->markPending($persona, auth()->id());
            }
            return;
        }
        $service->markPending($record, auth()->id());
    }
}
