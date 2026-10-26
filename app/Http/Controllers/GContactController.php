<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleContactsException;
use App\Services\GoogleContactsService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GContactController extends Controller
{
    public function signIn(Request $request, GoogleContactsService $service)
    {
        if (!$service->ready()) {
            return redirect()->route('contactos.index')->with('error', 'Primero aplique las migraciones de Google Contacts.');
        }
        if (!config('services.google.client_id') || !config('services.google.client_secret') || !config('services.google.redirect')) {
            return redirect()->route('contactos.index')->with('error', 'Complete la configuración Google del servidor.');
        }
        return Socialite::driver('google')->scopes([GoogleContactsService::SCOPE])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])->redirect();
    }

    public function handleCallback(Request $request, GoogleContactsService $service)
    {
        // Keep Socialite's state validation; never use stateless() for this flow.
        try {
            if ($request->has('error')) {
                $request->session()->forget('state');
                return redirect()->route('contactos.index')->with('error', 'No se autorizó Google Contacts. Puede volver a conectar.');
            }
            $service->connect(Socialite::driver('google')->user(), (int) $request->user()->id);
            $request->session()->forget(['GContactToken', 'GContactTokenExpiration', 'previous_url']);
            return redirect()->route('contactos.index')->with('success', 'Google Contacts conectado. Ya puede solicitar la sincronización.');
        } catch (Throwable $e) {
            $message = $e instanceof GoogleContactsException ? $e->getMessage()
                : 'No se pudo autorizar Google Contacts. Inicie nuevamente la conexión y compruebe la configuración.';
            return redirect()->route('contactos.index')->with('error', $message);
        }
    }

    // Compatibility for existing forms. Never return the token itself.
    public function getTokenExpiration()
    {
        $account = app(GoogleContactsService::class)->account();
        if (!$account || !$account->isConnected() || !$account->expires_at) {
            return '0:00';
        }
        $seconds = max(0, now()->diffInSeconds($account->expires_at, false));
        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
