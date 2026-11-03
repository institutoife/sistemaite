@include('user.access.errors')
@if(!isset($editando) && auth()->check() && app(\App\Services\AccountSecurity::class)->ready())
    @php
        $sucursalAccess = app(\App\Services\SedeAccess::class);
        $sucursalesInscripcion = \App\Models\CrmSede::whereIn('id', $sucursalAccess->allowedIds(auth()->user()))->orderBy('nombre')->get();
        $sucursalInscripcion = old('sede_id', $sucursalAccess->active());
    @endphp
    <div class="form-group">
        <label for="inscripcion-sede">Sucursal</label>
        <select id="inscripcion-sede" name="sede_id" class="form-control @error('sede_id') is-invalid @enderror" required aria-describedby="inscripcion-sede-ayuda">
            <option value="">Seleccione la Sucursal</option>
            @foreach($sucursalesInscripcion as $sucursal)
                <option value="{{ $sucursal->id }}" {{ (string)$sucursalInscripcion === (string)$sucursal->id ? 'selected' : '' }}>{{ $sucursal->nombre }}</option>
            @endforeach
        </select>
        <small id="inscripcion-sede-ayuda" class="form-text text-muted">Elija dónde se atenderá esta inscripción. También se activará esa Sucursal para configurar sus clases.</small>
        @if($sucursalesInscripcion->isEmpty())
            <p class="text-danger mt-2">No tiene sucursales autorizadas. Solicite la asignación a un administrador.</p>
        @endif
    </div>
@endif
