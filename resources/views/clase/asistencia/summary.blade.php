@php
    $support=$tab['canal']==='apoyo';
    $summaryViews=app(\App\Services\ClassAttendanceView::class);
    $plan=$summaryViews->objective($support?optional($enrollment->modalidad)->modalidad:optional($enrollment->asignatura)->asignatura);
    $level=$support?$summaryViews->objective(optional(optional($enrollment->modalidad)->nivel)->nivel):'Computación';
    $objective=$support?$summaryViews->objective($enrollment->objetivo):'';
    $metricTones=['Asistidas'=>'positive','Faltas'=>'danger','Recuperadas'=>'positive','Pendientes de recuperación'=>'warning'];
@endphp
<section class="attendance-summary" aria-label="Resumen de {{ $support?'inscripción':'matrícula' }} #{{ $enrollment->id }}">
    <header class="attendance-summary-header">
        <div class="attendance-summary-identity"><span>{{ $support?'Inscripción':'Matrícula' }}</span><h2>#{{ $enrollment->id }}</h2></div>
        @if($level)<span class="attendance-summary-level">{{ $level }}</span>@endif
        @if($objective)<p class="attendance-objective">{{ $objective }}</p>@endif
        <p class="attendance-summary-plan" title="{{ $support?'Modalidad / plan':'Curso' }}">{{ $plan ?: 'Sin especificar' }}</p>
    </header>
    <div class="attendance-summary-body">
            <dl class="attendance-summary-metrics" aria-label="Estadísticas de asistencia">
                @foreach($tab['stats'] as $label=>$count)
                    <div class="attendance-summary-metric attendance-summary-metric--{{ $metricTones[$label]??'normal' }}">
                        <dt title="{{ $label }}" aria-label="{{ $label }}">{{ $label==='Pendientes de recuperación'?'Pendientes':$label }}</dt>
                        <dd>{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>
        @canany($support?['Listar Pagos','Reporte Pagos']:['Listar Pagoscomputacion','Reporte Pagos'])
            @php($summaryBalance=max(0,$enrollment->costo-$tab['totalPagado']))
            <dl class="attendance-summary-finance" aria-label="Resumen de pagos">
                <div class="attendance-finance-card attendance-finance-card--total">
                    <dt><i class="fas fa-coins" aria-hidden="true"></i>Monto</dt>
                    <dd><span class="attendance-finance-currency">Bs</span> {{ number_format($enrollment->costo,2) }}</dd>
                </div>
                <div class="attendance-finance-card attendance-finance-card--paid">
                    <dt><i class="fas fa-hand-holding-usd" aria-hidden="true"></i>A cuenta</dt>
                    <dd><span class="attendance-finance-currency">Bs</span> {{ number_format($tab['totalPagado'],2) }}</dd>
                </div>
                <div class="attendance-finance-card {{ $summaryBalance>0?'attendance-finance-card--due':'attendance-finance-card--paid' }}">
                    <dt><i class="fas fa-wallet" aria-hidden="true"></i>Saldo</dt>
                    <dd><span class="attendance-finance-currency">Bs</span> {{ number_format($summaryBalance,2) }}</dd>
                </div>
            </dl>
        @else
            <p class="attendance-summary-payment-access">No tienes permiso para consultar los pagos de esta {{ $support?'inscripción':'matrícula' }}.</p>
        @endcanany
    </div>
</section>
