<?php

namespace App\Services;

use App\Exceptions\GoogleContactsException;
use App\Jobs\SyncGoogleContact;
use App\Models\GoogleContactAccount;
use App\Models\Persona;
use App\Models\Comentario;
use App\Models\Prospecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleContactsService
{
    public const SCOPE = 'https://www.googleapis.com/auth/contacts';
    private const API = 'https://people.googleapis.com/v1/';
    private const FIELDS = 'metadata,names,phoneNumbers,birthdays,biographies,userDefined';
    private const IDENTITY_FIELDS = 'metadata,names,phoneNumbers,userDefined';
    public const SOURCES = ['persona' => Persona::class, 'prospecto' => Prospecto::class, 'comentario' => Comentario::class];
    private $schemaReady;

    public function ready(): bool
    {
        if ($this->schemaReady === null) {
            $this->schemaReady = Schema::hasTable('google_contact_accounts') && Schema::hasColumn('personas', 'gcontact_photo_hash')
                && Schema::hasColumn('prospectos', 'gcontact_sync_status') && Schema::hasColumn('comentarios', 'gcontact_sync_status');
        }
        return $this->schemaReady;
    }

    public function account(): ?GoogleContactAccount
    {
        return $this->ready() ? GoogleContactAccount::where('slot', 'central')->first() : null;
    }

    public function modelClass(string $source): string
    {
        if (!isset(self::SOURCES[$source])) {
            throw new GoogleContactsException('invalid_source', 'El tipo de contacto no es válido.');
        }
        return self::SOURCES[$source];
    }

    public function sourceFor(Model $record): string
    {
        foreach (self::SOURCES as $source => $class) {
            if ($record instanceof $class) {
                return $source;
            }
        }
        throw new GoogleContactsException('invalid_source', 'El tipo de contacto no es válido.');
    }

    public function eligibleQuery(string $source = 'persona')
    {
        // Persona is the shared identity for teachers, CRM leads, students and other roles.
        // The role or the absence of a phone must not hide a person's contact or updates.
        $class = $this->modelClass($source);
        $query = $class::query();
        // A converted enquiry shares its person's contact, instead of creating another one.
        return $source === 'comentario' || ($source === 'prospecto' && Schema::hasColumn('prospectos','persona_id'))
            ? $query->whereNull('persona_id') : $query;
    }

    private function locked(callable $callback)
    {
        $result = Cache::lock('ife:gcontact:central', 180)->get($callback);
        if ($result === false) {
            throw new GoogleContactsException('busy', 'Google Contacts está procesando otra operación. Se reintentará.', true);
        }
        return $result;
    }

    public function connect($user, int $actorId): void
    {
        if (!in_array(self::SCOPE, (array) $user->approvedScopes, true)) {
            throw new GoogleContactsException('missing_scope', 'Debe autorizar el permiso de Google Contacts.');
        }
        $this->locked(function () use ($user, $actorId) {
            DB::transaction(function () use ($user, $actorId) {
                $account = GoogleContactAccount::where('slot', 'central')->lockForUpdate()->first();
                if ($account && $account->google_id !== (string) $user->getId()) {
                    throw new GoogleContactsException('account_mismatch', 'Reconecte la misma cuenta central de Google para conservar los contactos existentes.');
                }
                if (!$user->getId() || !$user->token || (!$user->refreshToken && (!$account || !$account->refresh_token))) {
                    throw new GoogleContactsException('missing_refresh_token', 'Google no entregó una autorización renovable. Vuelva a conectar y acepte el acceso solicitado.');
                }
                $account = $account ?: new GoogleContactAccount([
                    'slot' => 'central', 'google_id' => (string) $user->getId(), 'installation_id' => (string) Str::uuid(),
                ]);
                $account->fill([
                    'email' => $user->getEmail(), 'access_token' => $user->token,
                    'refresh_token' => $user->refreshToken ?: $account->refresh_token,
                    'expires_at' => now()->addSeconds(max(0, (int) $user->expiresIn)),
                    'requires_reconnection' => false, 'disconnected_at' => null, 'connected_by' => $actorId,
                ])->save();
                Log::info('Google Contacts conectado', ['account_id' => $account->id, 'user_id' => $actorId]);
            });
        });
    }

    public function disconnect(int $actorId): void
    {
        $this->locked(function () use ($actorId) {
            if ($account = $this->account()) {
                $account->update(['access_token' => null, 'refresh_token' => null, 'expires_at' => null,
                    'disconnected_at' => now(), 'requires_reconnection' => true]);
                Log::info('Google Contacts desconectado de IFE', ['account_id' => $account->id, 'user_id' => $actorId]);
            }
        });
    }

    public function markPending(Model $persona, ?int $actorId = null, bool $enqueue = true): void
    {
        if (!$this->ready()) {
            return;
        }
        $source = $this->sourceFor($persona);
        $class = $this->modelClass($source);
        $class::whereKey($persona->id)->update([
            'gcontact_sync_pending' => true, 'gcontact_sync_status' => 'pending',
            'gcontact_revision' => DB::raw('gcontact_revision + 1'),
            'gcontact_sync_attempts' => 0, 'gcontact_error_code' => null, 'gcontact_sync_error' => null,
        ]);
        if ($enqueue) {
            $this->enqueue($persona->id, $actorId, $source);
        }
    }

    public function markPersonAndGuardians(int $id, ?int $actorId = null): void
    {
        if (!$this->ready() || !($person = Persona::with('apoderados')->find($id))) {
            return;
        }
        $this->markPending($person, $actorId);
        foreach ($person->apoderados->unique('id') as $guardian) {
            $this->markPending($guardian, $actorId);
        }
    }

    public function enqueue(int $personaId, ?int $actorId = null, string $source = 'persona'): void
    {
        $account = $this->account();
        if ($account && $account->isConnected() && !$account->isPaused() && $this->eligibleQuery($source)->whereKey($personaId)->exists()) {
            // Acquire the unique-job lock only after commit; a rollback must not leave it orphaned.
            $dispatch = function () use ($personaId, $actorId, $source) {
                SyncGoogleContact::dispatch($personaId, $actorId, $source)->afterCommit();
            };
            if (DB::transactionLevel() > 0) {
                DB::afterCommit($dispatch);
            } else {
                $dispatch();
            }
        }
    }

    public function setPaused(bool $paused, int $actorId): void
    {
        DB::transaction(function () use ($paused, $actorId) {
            $account = GoogleContactAccount::where('slot', 'central')->lockForUpdate()->first();
            if (!$account) {
                throw new GoogleContactsException('reconnect', 'Conecte primero la cuenta central de Google.');
            }
            if ($account->isPaused() === $paused) {
                return;
            }
            $account->update(['sync_paused_at' => $paused ? now() : null,
                'sync_paused_by' => $paused ? $actorId : null]);
            Log::info($paused ? 'Sincronización Google pausada' : 'Sincronización Google reanudada',
                ['account_id' => $account->id, 'user_id' => $actorId]);
        });
        if (!$paused) {
            $this->enqueuePending();
        }
    }

    public function enqueuePending(int $limit = 100): int
    {
        $account = $this->account();
        if (!$account || !$account->isConnected() || $account->isPaused()) {
            return 0;
        }
        $remaining = max(1, min(1000, $limit));
        $count = 0;
        $sources = array_keys(self::SOURCES);
        foreach ($sources as $index => $source) {
            $share = (int) ceil($remaining / (count($sources) - $index));
            $ids = $this->eligibleQuery($source)->where('gcontact_sync_pending', true)
                ->whereIn('gcontact_sync_status', ['pending', 'idle'])->orderBy('id')->limit($share)->pluck('id');
            foreach ($ids as $id) {
                $this->enqueue($id, null, $source);
            }
            $count += $ids->count();
            $remaining -= $ids->count();
        }
        return $count;
    }

    private function accessToken(GoogleContactAccount $account): string
    {
        if (!$account->isConnected()) {
            throw new GoogleContactsException('reconnect', 'Google Contacts requiere reconexión.');
        }
        if ($account->access_token && $account->expires_at && $account->expires_at->gt(now()->addMinute())) {
            return $account->access_token;
        }
        try {
            $response = Http::asForm()->withOptions(['connect_timeout' => 5])->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $account->refresh_token, 'grant_type' => 'refresh_token',
            ]);
        } catch (ConnectionException $e) {
            throw $this->connectionFailure($e, true);
        }
        if (!$response->successful()) {
            if ($response->status() === 429 || $response->serverError()) {
                throw new GoogleContactsException('oauth_unavailable', 'Google no está disponible para renovar la autorización.', true);
            }
            $account->update(['requires_reconnection' => true, 'access_token' => null]);
            throw new GoogleContactsException('reconnect', 'La autorización de Google dejó de ser válida. Reconecte la cuenta central.');
        }
        if (!$response->json('access_token') || !(int) $response->json('expires_in')) {
            throw new GoogleContactsException('oauth_invalid_response', 'Google devolvió una autorización incompleta.', true);
        }
        $account->update([
            'access_token' => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token') ?: $account->refresh_token,
            'expires_at' => now()->addSeconds((int) $response->json('expires_in')),
        ]);
        return $account->access_token;
    }

    private function request(GoogleContactAccount $account, string $method, string $path, array $data = []): array
    {
        $token = $this->accessToken($account);
        try {
            $client = Http::withToken($token)->acceptJson()->withOptions(['connect_timeout' => 5])->timeout(15);
            $response = $client->{$method}(self::API.$path, $data);
        } catch (ConnectionException $e) {
            throw $this->connectionFailure($e);
        }
        if ($response->successful()) {
            if (is_array($response->json())) {
                return $response->json();
            }
            throw new GoogleContactsException('invalid_response', 'Google respondió sin datos válidos. El resultado requiere comprobación.');
        }
        $status = $response->status();
        if ($status === 401) {
            $account->update(['requires_reconnection' => true, 'access_token' => null]);
            throw new GoogleContactsException('unauthorized', 'Google requiere reconectar la cuenta central.');
        }
        if ($status === 403) {
            throw new GoogleContactsException('forbidden', 'Google rechazó el permiso. Compruebe People API y la autorización de contactos.');
        }
        if ($status === 404) {
            throw new GoogleContactsException('not_found', 'No se encontró el contacto en esta cuenta. Se conserva el enlace para revisión.');
        }
        if ($status === 429) {
            throw new GoogleContactsException('rate_limit', 'Google alcanzó su límite de solicitudes. Se reintentará.', true);
        }
        if ($status === 400 && strpos(json_encode($response->json()), 'failedPrecondition') !== false) {
            throw new GoogleContactsException('conflict', 'El contacto cambió en Google. Se volverán a consultar sus datos.', true);
        }
        throw new GoogleContactsException($status >= 500 ? 'unavailable' : 'invalid_contact',
            $status >= 500 ? 'Google no está disponible. Se reintentará.' : 'Google rechazó los datos del contacto. Revise su información.', $status >= 500);
    }

    private function payload(Model $persona): array
    {
        $name = $persona instanceof Prospecto ? trim((string)$persona->nombre) : trim($persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom);
        if ($persona instanceof Comentario && strtolower($name) === 'anonymous') {
            $name = '';
        }
        $phone = app(TelefonoService::class)->normalize($persona->telefono);
        if (!$phone && !app(TelefonoService::class)->isMissing($persona->telefono)) {
            throw new GoogleContactsException('invalid_phone', 'Revise el número de teléfono antes de sincronizar.');
        }
        if ($name === '' && ($persona instanceof Persona || !$phone)) {
            throw new GoogleContactsException('invalid_name', 'El contacto necesita un nombre o, para un prospecto o consulta, un teléfono válido.');
        }
        $payload = ['names' => $name === '' ? [] : ($persona instanceof Persona
            ? [['givenName' => (string) $persona->nombre, 'familyName' => trim($persona->apellidop.' '.$persona->apellidom)]]
            : [['givenName' => $name]]),
            'phoneNumbers' => $phone ? [['value' => $phone, 'type' => 'mobile']] : []];
        if ($persona instanceof Prospecto) {
            $payload['names']=[['givenName'=>$name!==''?$name:'Prospecto IFE | '.(strpos((string)$phone,'+591')===0?substr($phone,4):$phone)]];
            if ($name!=='') { $payload['names'][0]['honorificSuffix']='| Prospecto IFE'; }
        }
        if ($persona instanceof Persona) {
            $payload = array_merge($payload, app(GoogleContactProfile::class)->fields($persona));
        }
        return $payload;
    }

    private function connectionFailure(ConnectionException $exception, bool $oauth = false): GoogleContactsException
    {
        // Extract only the numeric cURL code; exception messages may contain sensitive URLs.
        preg_match('/cURL error (\d+)/i', $exception->getMessage(), $match);
        $code = (int) ($match[1] ?? 28);
        $prefix = $oauth ? 'oauth_' : '';
        if (in_array($code, [35, 51, 58, 60, 77], true)) {
            return new GoogleContactsException($prefix.'tls',
                'No se pudo establecer una conexión segura con Google. Revise los certificados y la configuración TLS de PHP.');
        }
        if (in_array($code, [5, 6, 7], true)) {
            return new GoogleContactsException($prefix.'network',
                'El worker no pudo conectarse a Google. Revise su acceso a Internet, DNS y configuración de proxy.', true);
        }
        return new GoogleContactsException($prefix.'timeout', $oauth
            ? 'No se pudo renovar la autorización de Google a tiempo. Se reintentará.'
            : 'No se recibió respuesta de Google. El resultado será verificado antes de crear otro contacto.', true);
    }

    private function contactSource(array $remote): array
    {
        foreach ($remote['metadata']['sources'] ?? [] as $source) {
            if (($source['type'] ?? '') === 'CONTACT' && !empty($source['etag'])) {
                return $source;
            }
        }
        throw new GoogleContactsException('missing_source', 'Google no devolvió los metadatos del contacto. No se creará otro contacto.', true);
    }

    private function findByMarker(GoogleContactAccount $account, array $markers, float $started, array $payload): ?array
    {
        $page = null;
        $found = null;
        $possibleLegacy = false;
        $normalizeName = function ($name) {
            return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
        };
        $localName = $normalizeName(($payload['names'][0]['givenName'] ?? '').' '.($payload['names'][0]['familyName'] ?? ''));
        $localPhone = $payload['phoneNumbers'][0]['value'] ?? null;
        // A central account can exceed 20,000 contacts; complete every page before creating.
        for ($i = 0; $i < 50; $i++) {
            if (microtime(true) - $started > 100) {
                break;
            }
            // Do not download everybody's potentially long notes just to reconcile an identity.
            $params = ['personFields' => self::IDENTITY_FIELDS, 'pageSize' => 1000, 'sources' => 'READ_SOURCE_TYPE_CONTACT'];
            if ($page) {
                $params['pageToken'] = $page;
            }
            $data = $this->request($account, 'get', 'people/me/connections', $params);
            foreach ($data['connections'] ?? [] as $remote) {
                $remoteName = $remote['names'][0] ?? [];
                $structuredName = trim(($remoteName['givenName'] ?? '').' '.($remoteName['familyName'] ?? ''));
                $remoteName = $normalizeName($structuredName !== '' ? $structuredName
                    : preg_replace('/\s*\|.*$/u', '', $remoteName['displayName'] ?? ''));
                if (($localName !== '' && $remoteName === $localName) || (($localName === '' || strpos($localName,'prospecto ife | ')===0) && $localPhone)) {
                    foreach ($remote['phoneNumbers'] ?? [] as $remotePhone) {
                        if ($localPhone && app(TelefonoService::class)->normalize($remotePhone['value'] ?? null) === $localPhone) {
                            $possibleLegacy = true;
                        }
                    }
                    if (!$localPhone) {
                        // An incomplete import may have lost the phone that Google still has.
                        // Stop for review; a name match is not permission to merge identities.
                        $possibleLegacy = true;
                    }
                }
                foreach ($remote['userDefined'] ?? [] as $field) {
                    if (in_array(['key' => $field['key'] ?? '', 'value' => $field['value'] ?? ''], $markers, true)) {
                        if ($found && ($found['resourceName'] ?? null) !== ($remote['resourceName'] ?? null)) {
                            throw new GoogleContactsException('duplicate_remote', 'Google contiene más de un contacto con el identificador IFE. Requiere revisión.');
                        }
                        $found = $remote;
                    }
                }
            }
            $page = $data['nextPageToken'] ?? null;
            if (!$page) {
                if (!$found && $possibleLegacy) {
                    throw new GoogleContactsException('possible_legacy_contact', 'Ya existe un contacto con datos coincidentes. Revise el enlace anterior antes de crear otro contacto.');
                }
                return $found;
            }
        }
        throw new GoogleContactsException('reconciliation_incomplete', 'No terminó la comprobación de contactos. No se creará otro contacto.', true);
    }

    private function contactMarkers(Model $record, GoogleContactAccount $account, string $source): array
    {
        $markers = [['key' => 'IFE_'.strtoupper($source), 'value' => $account->installation_id.':'.$record->id]];
        if (!$record instanceof Persona) {
            return $markers;
        }
        $resource = $record->resourseName;
        $intent = $record->gcontact_create_started_at;
        // Called under the Google account lock: any in-flight enquiry creation has finished.
        // Recover the same resource (or uncertain creation) after an enquiry becomes a person.
        $linked=Comentario::where('persona_id',$record->id)->get();
        if (Schema::hasColumn('prospectos','persona_id')) { $linked=$linked->concat(Prospecto::where('persona_id',$record->id)->get()); }
        foreach ($linked as $enquiry) {
            $markers[] = ['key' => $enquiry instanceof Prospecto?'IFE_PROSPECTO':'IFE_COMENTARIO', 'value' => $account->installation_id.':'.$enquiry->id];
            if ($enquiry->gcontact_account_id && (int) $enquiry->gcontact_account_id !== (int) $account->id) {
                throw new GoogleContactsException('account_mismatch', 'La consulta vinculada pertenece a otra conexión de Google. Requiere revisión.');
            }
            if ($enquiry->resourseName) {
                if ($resource && $resource !== $enquiry->resourseName) {
                    throw new GoogleContactsException('contact_link_conflict', 'La persona y su consulta tienen contactos Google distintos. Revise los enlaces antes de continuar.');
                }
                $resource = $enquiry->resourseName;
            }
            if ($enquiry->gcontact_create_started_at && !$intent) {
                $intent = $enquiry->gcontact_create_started_at;
            }
        }
        if ($resource !== $record->resourseName || $intent !== $record->gcontact_create_started_at) {
            Persona::whereKey($record->id)->update(['resourseName' => $resource,
                'gcontact_create_started_at' => $intent, 'gcontact_account_id' => $account->id]);
            $record->resourseName = $resource;
            $record->gcontact_create_started_at = $intent;
        }
        return $markers;
    }

    public function sync(int $personaId, ?int $actorId = null, string $sourceType = 'persona'): void
    {
        $model = $this->modelClass($sourceType);
        $this->locked(function () use ($personaId, $actorId, $sourceType, $model) {
            $started = microtime(true);
            $persona = $this->eligibleQuery($sourceType)->find($personaId);
            if (!$persona || !$persona->gcontact_sync_pending) {
                return;
            }
            $account = $this->account();
            if ($account && $account->isPaused()) {
                throw new GoogleContactsException('paused', 'La sincronización está pausada por un administrador.');
            }
            if (!$account || !$account->isConnected()) {
                throw new GoogleContactsException('reconnect', 'Conecte Google Contacts para procesar los pendientes.');
            }
            if ($persona->gcontact_account_id && (int) $persona->gcontact_account_id !== (int) $account->id) {
                throw new GoogleContactsException('account_mismatch', 'El contacto pertenece a otra conexión de Google. Requiere revisión.');
            }
            $markers = $this->contactMarkers($persona, $account, $sourceType);
            $revision = (int) $persona->gcontact_revision;
            $payload = $this->payload($persona);
            $profile = app(GoogleContactProfile::class);
            $photo = null;
            $photoWarning = null;
            if ($persona instanceof Persona) {
                try { $photo = $profile->photo($persona); }
                catch (GoogleContactsException $exception) {
                    if (!in_array($exception->reason, ['invalid_photo', 'photo_extension'], true)) { throw $exception; }
                    // A missing legacy upload must not prevent names and phone numbers from reaching Google.
                    // Keep a visible warning and never clear an existing remote photograph.
                    $photoWarning = $exception;
                }
            }
            $hash = hash('sha256', json_encode([$payload, $photo['hash'] ?? null]));
            $marker = $markers[0];
            $model::whereKey($personaId)->update(['gcontact_sync_status' => 'processing',
                'gcontact_sync_attempts' => DB::raw('gcontact_sync_attempts + 1'), 'gcontact_sync_last_attempt_at' => now()]);

            if ($persona->resourseName) {
                if (!preg_match('~^people/[A-Za-z0-9_-]+$~', $persona->resourseName)) {
                    throw new GoogleContactsException('invalid_resource', 'El identificador Google guardado necesita revisión.');
                }
                $remote = $this->request($account, 'get', $persona->resourseName, ['personFields' => self::FIELDS]);
            } else {
                $remote = $this->findByMarker($account, $markers, $started, $payload);
                if ($remote) {
                    if (!preg_match('~^people/[A-Za-z0-9_-]+$~', $remote['resourceName'] ?? '')) {
                        throw new GoogleContactsException('invalid_resource', 'Google devolvió un identificador inválido durante la comprobación.');
                    }
                    // Fetch current notes and etag only for the contact actually being updated.
                    $remote = $this->request($account, 'get', $remote['resourceName'], ['personFields' => self::FIELDS]);
                }
                if (!$remote && $persona->gcontact_create_started_at) {
                    throw new GoogleContactsException('uncertain_create', 'La creación anterior no pudo confirmarse. Revise Google; se evita crear un duplicado.');
                }
            }

            if (!$remote) {
                // Durable intent survives timeouts, worker crashes and failure to save the response.
                $model::whereKey($personaId)->update(['gcontact_create_started_at' => now(), 'gcontact_account_id' => $account->id]);
                $payload['userDefined'] = [$marker];
                if (isset($payload['biographies'])) {
                    $payload['biographies'] = $profile->biography($payload['biographies'][0]['value']);
                }
                try {
                    $remote = $this->request($account, 'post', 'people:createContact?personFields='.self::FIELDS, $payload);
                } catch (GoogleContactsException $e) {
                    if (in_array($e->reason, ['unauthorized', 'forbidden', 'rate_limit', 'invalid_contact', 'not_found', 'reconnect', 'oauth_timeout', 'oauth_network', 'oauth_tls', 'oauth_unavailable', 'oauth_invalid_response'], true)) {
                        $model::whereKey($personaId)->update(['gcontact_create_started_at' => null]);
                    }
                    throw $e;
                }
            } else {
                $source = $this->contactSource($remote);
                if ($persona instanceof Prospecto || collect($markers)->contains(function ($marker) { return $marker['key']==='IFE_PROSPECTO'; })) {
                    $owned=collect($remote['userDefined']??[])->contains(function ($field) use ($markers) {
                        return in_array(['key'=>$field['key']??'','value'=>$field['value']??''],$markers,true);
                    });
                    $remoteName=$remote['names'][0]??[];
                    $remoteText=trim(($remoteName['givenName']??'').' '.($remoteName['familyName']??'')) ?: trim($remoteName['displayName']??'');
                    $synthetic=$remoteText===''||preg_match('/^Prospecto IFE\s*\|\s*[+0-9 ()-]+$/iu',$remoteText)||app(TelefonoService::class)->normalize($remoteText)!==null;
                    $samePhone=collect($remote['phoneNumbers']??[])->contains(function ($number) use ($payload) {
                        return isset($payload['phoneNumbers'][0]['value'])&&app(TelefonoService::class)->normalize($number['value']??null)===$payload['phoneNumbers'][0]['value'];
                    });
                    // A saved legacy link alone is not permission to overwrite a personal contact's real name.
                    if (!$owned&&(!$synthetic||!$samePhone)) { throw new GoogleContactsException('unverified_contact_owner','No se pudo comprobar que el contacto Google pertenece a este Prospecto IFE. Revise su enlace antes de modificarlo.'); }
                    if ($persona instanceof Prospecto&&trim((string)$persona->nombre)===''&&!$synthetic) { unset($payload['names']); }
                }
                $fields = [];
                foreach ($remote['userDefined'] ?? [] as $field) {
                    if (in_array($field['key'] ?? '', ['IFE_PERSONA', 'IFE_PROSPECTO', 'IFE_COMENTARIO'], true)) {
                        if (!in_array(['key' => $field['key'], 'value' => $field['value'] ?? ''], $markers, true)) {
                            throw new GoogleContactsException('marker_mismatch', 'El contacto Google está vinculado a otra persona IFE. Requiere revisión.');
                        }
                        if ($field['key'] !== $marker['key']) {
                            $fields[] = ['key' => $field['key'], 'value' => $field['value']];
                        }
                    } else {
                        $fields[] = ['key' => $field['key'], 'value' => $field['value']];
                    }
                }
                $payload['userDefined'] = array_merge($fields, [$marker]);
                $payload['metadata'] = ['sources' => [$source]];
                $payload['resourceName'] = $remote['resourceName'];
                $updateFields = ['userDefined'];
                if (!empty($payload['names'])) {
                    $updateFields[] = 'names';
                } else {
                    unset($payload['names']);
                }
                if (!empty($payload['phoneNumbers'])) {
                    $updateFields[] = 'phoneNumbers';
                } else {
                    // Missing local data is not a request to erase a phone kept in Google.
                    unset($payload['phoneNumbers']);
                }
                if (isset($payload['birthdays'])) {
                    $updateFields[] = 'birthdays';
                }
                if (isset($payload['biographies'])) {
                    $payload['biographies'] = $profile->biography($payload['biographies'][0]['value'], $remote);
                    $updateFields[] = 'biographies';
                }
                $remote = $this->request($account, 'patch', $remote['resourceName'].':updateContact?updatePersonFields='.implode(',', $updateFields).'&personFields='.self::FIELDS, $payload);
            }
            if (empty($remote['resourceName']) || !preg_match('~^people/[A-Za-z0-9_-]+$~', $remote['resourceName'])) {
                throw new GoogleContactsException('invalid_response', 'Google no devolvió un identificador válido. No se repetirá la creación sin comprobarla.');
            }
            // Save the permanent link before validating the rest of the response.
            $model::whereKey($personaId)->update(['resourseName' => $remote['resourceName'],
                'gcontact_account_id' => $account->id, 'gcontact_create_started_at' => null]);
            $source = $this->contactSource($remote);
            $model::whereKey($personaId)->update(['etag' => $source['etag']]);
            if ($photo && $photo['hash'] !== $persona->gcontact_photo_hash) {
                // A failed photo update keeps the permanent contact link, so retries cannot CREATE again.
                $result = $this->request($account, 'patch', $remote['resourceName'].':updateContactPhoto', [
                    'photoBytes' => $photo['bytes'], 'personFields' => self::FIELDS,
                    'sources' => ['READ_SOURCE_TYPE_CONTACT'],
                ]);
                $updated = $result['person'] ?? [];
                if (($updated['resourceName'] ?? null) !== $remote['resourceName']) {
                    throw new GoogleContactsException('photo_response', 'Google no confirmó la fotografía. Se conserva el enlace y se reintentará.', true);
                }
                $source = $this->contactSource($updated);
                Persona::whereKey($personaId)->update(['gcontact_photo_hash' => $photo['hash'], 'etag' => $source['etag']]);
            }
            $model::whereKey($personaId)->update(['gcontact_payload_hash' => $hash, 'gcontact_synced_at' => now()]);
            $saved = $model::whereKey($personaId)->where('gcontact_revision', $revision)->update([
                'gcontact_sync_pending' => false, 'gcontact_sync_status' => 'synced',
                'gcontact_sync_error' => $photoWarning ? 'Datos sincronizados. '.$photoWarning->getMessage().' Se conserva la foto existente en Google.' : null,
                'gcontact_error_code' => $photoWarning ? $photoWarning->reason : null,
            ]);
            if (!$saved) {
                $model::whereKey($personaId)->update(['gcontact_sync_pending' => true, 'gcontact_sync_status' => 'pending']);
            }
            if ($photoWarning) {
                Log::warning('Fotografía Google omitida', ['source' => $sourceType, 'record_id' => $personaId, 'code' => $photoWarning->reason]);
            }
            Log::info('Contacto Google sincronizado', ['source' => $sourceType, 'record_id' => $personaId, 'user_id' => $actorId]);
        });
    }
}
