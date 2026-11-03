@if(app(\App\Services\PresentAttendanceService::class)->widget())
    <li class="nav-item">
        <button type="button" class="btn nav-link position-relative" data-presence-open title="Salidas pendientes" aria-label="Salidas pendientes" aria-controls="ife-exit-modal">
            <i class="fas fa-door-open" aria-hidden="true"></i>
            <span class="badge badge-danger navbar-badge d-none" data-presence-count>0</span>
        </button>
    </li>
@endif
