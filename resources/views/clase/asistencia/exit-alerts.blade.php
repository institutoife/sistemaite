@if(app(\App\Services\PresentAttendanceService::class)->widget())
    <link rel="stylesheet" href="{{ asset('assets/css/presentes.css') }}?v={{ filemtime(public_path('assets/css/presentes.css')) }}">
    <div id="ife-presence" data-status-url="{{ route('presentes.estado') }}" data-user="{{ auth()->id() }}" data-scope="{{ hash('sha256',app(\App\Services\SedeAccess::class)->contextKey()) }}" data-csrf="{{ csrf_token() }}">
        <div class="ife-exit-banner alert alert-warning d-none shadow-sm" role="status" aria-live="polite">
            <span data-presence-summary></span>
            <button type="button" class="btn btn-sm btn-dark ml-2" data-presence-open>Revisar salidas</button>
        </div>
        <div class="modal fade" id="ife-exit-modal" tabindex="-1" aria-labelledby="ife-exit-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="ife-exit-title">Salidas pendientes</h5><button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    <p class="small text-muted">Avisar conserva la asistencia abierta. Posponer oculta el aviso temporalmente para tu usuario. Horarios de Bolivia.</p>
                    <div class="alert alert-danger d-none" data-presence-error role="alert"></div>
                    <div data-presence-pending></div>
                </div>
                <div class="modal-footer"><a class="btn btn-ife" href="{{ route('clase.presentes') }}">Ver lista de presentes</a><button class="btn btn-secondary" type="button" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button></div>
            </div></div>
        </div>
    </div>
    <script src="{{ asset('assets/js/presentes.js') }}?v={{ filemtime(public_path('assets/js/presentes.js')) }}" defer></script>
@endif
