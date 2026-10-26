<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleContactsException;
use App\Models\Persona;
use App\Models\Prospecto;
use App\Models\Comentario;
use Illuminate\Database\Eloquent\Model;
use App\Services\GoogleContactsService;
use App\Services\GoogleContactsRuntime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleContactsController extends Controller
{
    public function index(Request $request, GoogleContactsService $service, GoogleContactsRuntime $runtime)
    {
        $request->validate(['q' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,processing,synced,error,retrying,idle',
            'source' => 'sometimes|in:persona,prospecto,comentario',
            'without_phone' => 'sometimes|boolean']);
        $ready = $service->ready();
        $account = $ready ? $service->account() : null;
        $source = (string) $request->query('source', 'persona');
        $query = $service->eligibleQuery($source);
        if ($source === 'persona') {
            $query->with(['estudiante', 'computacion', 'docente', 'administrativo', 'cliservicio', 'hijos:id,nombre,apellidop,apellidom']);
        }
        if ($request->boolean('without_phone')) {
            // NULL, blank and legacy all-zero placeholders. Supported by MariaDB and SQLite.
            $query->whereRaw("REPLACE(TRIM(COALESCE(telefono, '')), '0', '') = ''");
        }
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($query) use ($search, $source) {
                $query->where('telefono', 'like', '%'.$search.'%');
                if ($source !== 'prospecto') {
                    $query->orWhere('nombre', 'like', '%'.$search.'%');
                }
                if ($source === 'persona') {
                    $query->orWhere('apellidop', 'like', '%'.$search.'%')->orWhere('apellidom', 'like', '%'.$search.'%');
                }
                if (ctype_digit($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }
        $status = $request->query('status');
        if ($ready && in_array($status, ['pending', 'processing', 'synced', 'error', 'retrying', 'idle'], true)) {
            if ($status === 'pending') {
                $query->where('gcontact_sync_pending', true)->whereIn('gcontact_sync_status', ['pending', 'idle']);
            } else {
                $query->where('gcontact_sync_status', $status);
            }
        }
        $personas = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();
        $worker = $runtime->status();
        $counts = ['pending' => 0, 'processing' => 0, 'synced' => 0, 'error' => 0];
        if ($ready) {
            // Global counters describe the shared queue, independently of the current list filters.
            foreach (array_keys(GoogleContactsService::SOURCES) as $countSource) {
                $groups = $service->eligibleQuery($countSource)->selectRaw('gcontact_sync_status, gcontact_sync_pending, COUNT(*) AS total')
                    ->groupBy('gcontact_sync_status', 'gcontact_sync_pending')->get();
                foreach ($groups as $group) {
                    if ($group->gcontact_sync_status === 'error') {
                        $counts['error'] += (int) $group->total;
                    } elseif ($group->gcontact_sync_pending) {
                        $counts[$group->gcontact_sync_status === 'processing' ? 'processing' : 'pending'] += (int) $group->total;
                    } elseif ($group->gcontact_sync_status === 'synced') {
                        $counts['synced'] += (int) $group->total;
                    }
                }
            }
        }
        return view('contactos.index', compact('ready', 'account', 'personas', 'worker', 'counts', 'source'));
    }

    public function pause(Request $request, GoogleContactsService $service)
    {
        return $this->changePause($request, $service, true);
    }

    public function resume(Request $request, GoogleContactsService $service)
    {
        return $this->changePause($request, $service, false);
    }

    private function changePause(Request $request, GoogleContactsService $service, bool $paused)
    {
        // An explicit Google permission alone does not grant this global administrator control.
        abort_unless($request->user()->hasRole('Admin'), 403);
        abort_unless($service->ready(), 503, 'Aplique las migraciones de Google Contacts.');
        try {
            $service->setPaused($paused, $request->user()->id);
            return redirect()->route('contactos.index')->with('success', $paused
                ? 'Sincronización pausada para todos. La operación en curso terminará; los demás cambios quedarán pendientes.'
                : 'Sincronización reanudada para todos. Los pendientes se procesarán cuando el proceso del servidor esté disponible.');
        } catch (GoogleContactsException $e) {
            return redirect()->route('contactos.index')->with('error', $e->getMessage());
        }
    }

    public function sync(Request $request, Persona $persona, GoogleContactsService $service)
    {
        return $this->syncRecord($request, $persona, $service, 'persona');
    }

    public function syncProspecto(Request $request, Prospecto $prospecto, GoogleContactsService $service)
    {
        return $this->syncRecord($request, $prospecto, $service, 'prospecto');
    }

    public function syncComentario(Request $request, Comentario $comentario, GoogleContactsService $service)
    {
        return $this->syncRecord($request, $comentario, $service, 'comentario');
    }

    private function syncRecord(Request $request, Model $persona, GoogleContactsService $service, string $source)
    {
        abort_unless($service->ready(), 503, 'Aplique las migraciones de Google Contacts.');
        abort_unless($service->eligibleQuery($source)->whereKey($persona->id)->exists(), 404);
        DB::transaction(function () use ($persona, $request, $service) {
            $persona->usuarios()->attach($request->user()->id);
            $service->markPending($persona, $request->user()->id);
        });
        Log::info('Sincronización Google solicitada', ['source' => $source, 'record_id' => $persona->id, 'user_id' => $request->user()->id]);
        $paused = $service->account() && $service->account()->isPaused();
        return redirect()->route('contactos.index', array_merge($request->only('q', 'status', 'without_phone'), $source === 'persona' ? [] : ['source' => $source]))
            ->with('success', $paused ? 'Contacto guardado como pendiente. Se procesará cuando un administrador reanude la sincronización.'
                : 'Contacto pendiente. Se procesará en segundo plano cuando Google esté conectado.');
    }

    public function disconnect(Request $request, GoogleContactsService $service)
    {
        try {
            $service->disconnect($request->user()->id);
            $request->session()->forget(['GContactToken', 'GContactTokenExpiration']);
            return redirect()->route('contactos.index')->with('success', 'Se desconectó Google de IFE. Los contactos y enlaces se conservan.');
        } catch (GoogleContactsException $e) {
            return redirect()->route('contactos.index')->with('error', $e->getMessage());
        }
    }
}
