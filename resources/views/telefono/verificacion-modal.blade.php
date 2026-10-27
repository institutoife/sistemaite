@can('Verificar Telefonos')
@if (app(\App\Services\PhoneVerificationService::class)->ready())
@php
    $phoneCheckRoot = request()->routeIs('personas.show', 'telefonos.persona', 'opcion.principal', 'opcion.computacion')
        && isset($persona) && $persona instanceof \App\Models\Persona ? $persona->id : 0;
@endphp
<dialog id="ife-phone-verification" class="ife-phone-verification" aria-labelledby="ife-phone-title"
    data-endpoint="{{ route('telefonos.verificacion.show', ['persona' => 0]) }}"
    data-root="{{ $phoneCheckRoot }}" data-token="{{ csrf_token() }}">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h2 class="h4 mb-0" id="ife-phone-title">Verificar teléfono con el cliente</h2>
        <button type="button" class="btn btn-sm btn-light" data-phone-action="close" aria-label="Cerrar">&times;</button>
    </div>
    <p class="font-weight-bold mb-1" data-phone-name></p>
    <p class="small text-muted" data-phone-context></p>
    <p data-phone-question></p>
    <p class="h4 mb-3" data-phone-current></p>
    <button type="button" class="btn btn-success mb-3" data-phone-action="confirm">Sigue con este número</button>
    <form data-phone-form>
        <div class="form-group">
            <label for="ife-phone-input">Número actual del cliente</label>
            <input id="ife-phone-input" class="form-control" name="phone" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="8 dígitos, por ejemplo 70000001">
            <small class="text-muted">Si cambió o no estaba registrado, consulte el número y guárdelo aquí.</small>
        </div>
        <p class="text-danger" role="alert" data-phone-error hidden></p>
        <button type="submit" class="btn btn-primary">Guardar y confirmar</button>
        <button type="button" class="btn btn-outline-secondary" data-phone-action="defer">Ahora no</button>
        <button type="button" class="btn btn-outline-primary" data-phone-action="reload" hidden>Volver a consultar</button>
    </form>
    <p class="small text-muted mt-3 mb-0">La confirmación se comparte con el equipo durante {{ app(\App\Services\PhoneVerificationService::class)->months() }} meses. «Ahora no» lo recuerda mañana a este usuario, sin confirmar el número.</p>
</dialog>
<style>
    .ife-phone-verification { width:min(94vw,550px); max-height:90vh; overflow:auto; border:0; border-top:5px solid #26baa5; border-radius:10px; padding:24px; box-shadow:0 12px 45px #0005; }
    .ife-phone-verification::backdrop { background:rgba(20,35,45,.55); }
</style>
<script src="{{ asset('assets/js/phone-verification.js') }}?v={{ filemtime(public_path('assets/js/phone-verification.js')) }}"></script>
@endif
@endcan
