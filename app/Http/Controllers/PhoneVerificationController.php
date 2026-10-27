<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function show(Request $request, Persona $persona, PhoneVerificationService $service)
    {
        $request->validate(['review_now' => 'sometimes|boolean']);
        return response()->json(['people' => $service->pending($persona, (int) $request->user()->id, $request->boolean('review_now')),
            'months' => $service->months()])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, Persona $persona, PhoneVerificationService $service)
    {
        abort_unless($service->ready(), 503, 'La verificación telefónica todavía no está disponible.');
        $data = $request->validate(['action' => 'required|in:confirm,update,defer', 'version' => 'required|regex:/^[a-f0-9]{64}$/',
            'phone' => 'nullable|string|max:25']);
        $service->save($persona, (int) $request->user()->id, $data['action'], $data['version'], $data['phone'] ?? null);
        return response()->json(['saved' => true, 'phone' => $persona->fresh()->telefono,
            'message' => $data['action'] === 'defer' ? 'Se recordará mañana; el número no se marcó como confirmado.' : 'Número confirmado con el cliente.']);
    }
}
