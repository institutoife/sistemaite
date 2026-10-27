<?php

namespace App\Services;

use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PhoneVerificationService
{
    public function ready(): bool
    {
        return Schema::hasTable('person_phone_verifications') && Schema::hasTable('person_phone_deferrals');
    }

    public function months(): int
    {
        return max(1, min(24, (int) config('google-contacts.phone_verification_months', 6)));
    }

    public function fingerprint(Persona $person): string
    {
        $phones = app(TelefonoService::class);
        $phone = $phones->isMissing($person->telefono) ? 'missing'
            : ($phones->normalize($person->telefono) ?: 'invalid:'.$person->telefono);
        return hash_hmac('sha256', $person->id.'|'.$phone, (string) config('app.key'));
    }

    public function pending(Persona $root, int $userId, bool $reviewNow = false): array
    {
        if (!$this->ready()) {
            return [];
        }
        $people = collect([$root])->merge($root->apoderados()->get())->unique('id');
        $checks = DB::table('person_phone_verifications')->whereIn('persona_id', $people->pluck('id'))->get()->keyBy('persona_id');
        $deferrals = DB::table('person_phone_deferrals')->where('user_id', $userId)
            ->whereIn('persona_id', $people->pluck('id'))->get()->keyBy('persona_id');
        $due = [];
        foreach ($people as $person) {
            $hash = $this->fingerprint($person);
            $check = $checks->get($person->id);
            $valid = (bool) app(TelefonoService::class)->normalize($person->telefono);
            if (!$reviewNow && $valid && $check && hash_equals($check->phone_hash, $hash)
                && Carbon::parse($check->verified_at)->addMonthsNoOverflow($this->months())->isFuture()) {
                continue;
            }
            $deferred = $deferrals->get($person->id);
            if (!$reviewNow && $deferred && hash_equals($deferred->phone_hash, $hash) && Carbon::parse($deferred->deferred_until)->isFuture()) {
                continue;
            }
            $due[] = ['id' => $person->id, 'name' => trim($person->nombre.' '.$person->apellidop.' '.$person->apellidom),
                'phone' => app(TelefonoService::class)->isMissing($person->telefono) ? '' : $person->telefono,
                'valid_phone' => $valid, 'version' => $hash,
                'relationship' => $person->id === $root->id ? 'Persona consultada' : 'Apoderado',
                'last_verified_at' => $check && hash_equals($check->phone_hash, $hash) ? $check->verified_at : null];
        }
        return $due;
    }

    public function save(Persona $person, int $userId, string $action, string $version, ?string $phone): void
    {
        DB::transaction(function () use ($person, $userId, $action, $version, $phone) {
            $current = Persona::whereKey($person->id)->lockForUpdate()->firstOrFail();
            if (!hash_equals($this->fingerprint($current), $version)) {
                throw new ConflictHttpException('Otro usuario cambió el teléfono. Vuelva a consultar la ficha antes de confirmar.');
            }
            if ($action === 'defer') {
                DB::table('person_phone_deferrals')->updateOrInsert(['persona_id' => $current->id, 'user_id' => $userId],
                    ['phone_hash' => $version, 'deferred_until' => now()->addDay()]);
                return;
            }
            $phones = app(TelefonoService::class);
            if ($action === 'update') {
                $normalized = $phones->normalize($phone);
                if (!$normalized || !preg_match('/^\+591[23467]\d{7}$/', $normalized)) {
                    throw ValidationException::withMessages(['phone' => 'Ingrese un número boliviano válido de 8 dígitos.']);
                }
                $current->telefono = substr($normalized, 4);
                // Normal Persona events schedule Google after commit, using the existing contact link.
                $current->save();
                DB::table('persona_persona')->where('persona_id_apoderado', $current->id)->update(['telefono' => $current->telefono]);
            } elseif (!$phones->normalize($current->telefono)) {
                throw ValidationException::withMessages(['phone' => 'Registre un teléfono válido antes de confirmarlo.']);
            }
            DB::table('person_phone_verifications')->updateOrInsert(['persona_id' => $current->id],
                ['phone_hash' => $this->fingerprint($current), 'verified_at' => now(), 'verified_by' => $userId]);
            DB::table('person_phone_deferrals')->where('persona_id', $current->id)->delete();
            $current->usuarios()->attach($userId);
        });
    }
}
