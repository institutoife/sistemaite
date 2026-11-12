<span class="d-inline-flex flex-wrap align-items-center">
    <span class="badge mr-1" style="background:#26BAA5;color:#fff">{{ $inscripcion->estado->estado ?? ($inscripcion->vigente ? 'VIGENTE' : 'NO VIGENTE') }}</span>
    <span class="badge badge-light" style="color:#375F7A">Sucursal: {{ $inscripcion->sucursal_origen ?: 'Sin registrar' }}</span>
</span>
