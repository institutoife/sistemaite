@if($program->adicional)
    <span class="badge badge-light" style="color:#375F7A;border:1px solid #26BAA5">{{ $program->adicional->etiqueta }}</span>
    @if($program->adicional->original)
        <small class="d-block">Original #{{ $program->adicional->original->id }}: {{ $program->adicional->original->fecha->format('d/m/Y') }} · Efectiva: {{ $program->fecha->format('d/m/Y') }}</small>
    @endif
@endif
@if($program->compensacion)
    @php $effective=$program->compensacion->programa; @endphp
    @if($effective)
        <small class="d-block text-info">{{ $program->compensacion->etiqueta }} #{{ $effective->id }}: {{ $effective->fecha->format('d/m/Y') }} · {{ optional($effective->estado)->estado }}</small>
    @endif
@endif
