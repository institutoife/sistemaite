(function () {
    'use strict';
    var timeout;
    function show(notice) {
        if (!notice || !notice.message) return;
        var previous = document.getElementById('ife-arrival-toast');
        if (previous) previous.remove();
        window.clearTimeout(timeout);
        var toast = document.createElement('div'), title = document.createElement('strong'), text = document.createElement('div'), close = document.createElement('button');
        toast.id = 'ife-arrival-toast';
        toast.className = 'ife-arrival-toast' + (notice.type === 'late' ? ' is-late' : '');
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        title.textContent = notice.title;
        text.textContent = notice.message;
        close.type = 'button';
        close.textContent = '×';
        close.setAttribute('aria-label', 'Cerrar aviso de horario');
        close.addEventListener('click', function () { toast.remove(); window.clearTimeout(timeout); });
        toast.append(title, text, close);
        document.body.appendChild(toast);
        timeout = window.setTimeout(function () { toast.remove(); }, 8000);
    }
    function save(notice) {
        try { if (notice) sessionStorage.setItem('ife-attendance-arrival', JSON.stringify(notice)); } catch (_) { /* Storage may be disabled. */ }
    }
    function restore() {
        var flash = document.querySelector('[data-attendance-arrival-notice]'), notice;
        try {
            notice = JSON.parse(sessionStorage.getItem('ife-attendance-arrival') || 'null');
            sessionStorage.removeItem('ife-attendance-arrival');
        } catch (_) { /* A server flash still works without browser storage. */ }
        if (flash) {
            try { notice = JSON.parse(flash.getAttribute('data-attendance-arrival-notice')); } catch (_) {}
        }
        show(notice);
    }
    window.IFEArrivalNotice = { show: show, save: save };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', restore);
    else restore();
})();
