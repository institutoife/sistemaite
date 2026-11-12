@if(session('attendance_arrival_notice'))
    <div hidden data-attendance-arrival-notice="{{ json_encode(session('attendance_arrival_notice'),JSON_UNESCAPED_UNICODE) }}"></div>
@endif
<script src="{{ asset('vistas/clase/arrival-notice.js') }}?v={{ is_file(public_path('vistas/clase/arrival-notice.js')) ? filemtime(public_path('vistas/clase/arrival-notice.js')) : 1 }}"></script>
