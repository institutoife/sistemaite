(function () {
    'use strict';
    var root = document.getElementById('ife-presence');
    if (!root || window.IFEPresentes) return;
    var rows = [], offset = 0, anchor = performance.now(), timer, inFlight = null, stopped = false, failures = 0, generation = 0, queued = false;
    var busy = new Set(), modal = document.getElementById('ife-exit-modal');
    var page = document.getElementById('ife-presentes-list');
    var timeFormat = new Intl.DateTimeFormat('es-BO', { timeZone: 'America/La_Paz', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    var dateFormat = new Intl.DateTimeFormat('es-BO', { timeZone: 'America/La_Paz', day: '2-digit', month: '2-digit', year: 'numeric' });
    function now() { return offset + performance.now() - anchor; }
    function esc(value) { var node = document.createElement('span'); node.textContent = value == null ? '' : String(value); return node.innerHTML.replace(/"/g,'&quot;'); }
    function clock(seconds) {
        var total = Math.max(0, Math.floor(seconds)), hours = Math.floor(total / 3600), minutes = Math.floor(total % 3600 / 60);
        return (hours ? hours + ' h ' : '') + minutes + ' min ' + String(total % 60).padStart(2, '0') + ' s';
    }
    function state(row, at) {
        var remaining = (Date.parse(row.fin) - at) / 1000;
        return remaining > 600 ? 'normal' : remaining > 0 ? 'warning' : remaining > -60 ? 'due' : 'overdue';
    }
    function pending(row) { return Date.parse(row.fin) <= now(); }
    function postponed(row) { return row.pospuesto_hasta && Date.parse(row.pospuesto_hasta) > now(); }
    function actions(row, notice) {
        var html = '<div class="presence-actions">';
        if (row.can_finish) {
            if (notice) {
                html += '<button type="button" class="btn btn-sm btn-light" data-presence-action="notify"' + (row.avisado_at ? ' disabled' : '') + '>Avisar al estudiante</button>';
                html += '<label class="sr-only" for="snooze-' + row.canal + '-' + row.id + '">Minutos para posponer</label><select class="form-control form-control-sm" id="snooze-' + row.canal + '-' + row.id + '" data-snooze-minutes><option value="5">5 min</option><option value="10">10 min</option><option value="15">15 min</option></select><button type="button" class="btn btn-sm btn-light" data-presence-action="snooze">Posponer</button>';
            }
            html += '<button type="button" class="btn btn-sm btn-ife" data-presence-action="finish">Marcar salida</button>';
        }
        if (row.edit_url) html += '<a class="btn btn-sm btn-light" href="' + esc(row.edit_url) + '">Editar</a>';
        html += '<a class="btn btn-sm btn-light" href="' + esc(row.history_url) + '">Historial</a></div>';
        return html;
    }
    function renderPage() {
        if (!page) return;
        var search = document.getElementById('presence-search').value.toLocaleLowerCase(), channel = document.getElementById('presence-channel').value;
        var filtered = rows.filter(function (r) {
            return (!channel || r.canal === channel) && [r.codigo,r.estudiante,r.docente,r.materia,r.sucursal,r.inscripcion_id].join(' ').toLocaleLowerCase().includes(search);
        });
        page.querySelector('[data-presence-total]').textContent = filtered.length + (filtered.length !== rows.length ? ' / ' + rows.length : '');
        page.querySelector('[data-presence-rows]').innerHTML = filtered.map(function (r) {
            var photo = window.IFEPhotos ? window.IFEPhotos.render(r.foto,'display',{name:r.estudiante},{height:40,alt:r.estudiante}) : '';
            return '<tr data-presence-key="' + r.key + '"><td>' + photo + ' <a href="' + esc(r.persona_url) + '">' + esc(r.estudiante) + '</a><small class="d-block">Persona #' + r.codigo + '</small></td><td>' + esc(r.tipo) + ' #' + r.inscripcion_id + '<small class="d-block">Asistencia #' + r.id + ' · Clase #' + r.programacion_id + '</small><strong class="d-block">' + esc(r.sucursal) + '</strong></td><td>' + esc(r.docente) + '<small class="d-block">' + esc(r.materia || 'Sin materia') + ' · ' + esc(r.aula || 'Sin aula') + '</small><small class="d-block">Registró: ' + esc(r.registrado_por) + '</small></td><td>' + dateFormat.format(new Date(r.inicio)) + '<span class="d-block presence-clock">' + timeFormat.format(new Date(r.inicio)) + ' – ' + timeFormat.format(new Date(r.fin)) + '</span></td><td data-elapsed class="presence-clock"></td><td><strong data-remaining class="presence-clock"></strong><small data-presence-state class="d-block"></small></td><td>' + actions(r, false) + '</td></tr>';
        }).join('') || '<tr><td colspan="7">No hay asistencias pendientes de salida con estos filtros.</td></tr>';
    }
    var pendingSignature = '';
    function renderPending(force) {
        var due = rows.filter(pending), visible = due.filter(function (r) { return !postponed(r); });
        document.querySelectorAll('[data-presence-count]').forEach(function (n) { n.textContent = visible.length; n.classList.toggle('d-none', !visible.length); });
        var banner = root.querySelector('.ife-exit-banner');
        banner.classList.toggle('d-none', !visible.length);
        root.querySelector('[data-presence-summary]').textContent = visible.length + (visible.length === 1 ? ' estudiante tiene la salida pendiente.' : ' estudiantes tienen la salida pendiente.');
        document.querySelectorAll('[data-presence-open]').forEach(function (button) { button.setAttribute('aria-label', 'Revisar ' + visible.length + ' salidas pendientes'); });
        var signature = due.map(function (r) { return [r.key,r.fin,r.inicio,r.estudiante,r.sucursal,r.docente,r.materia,r.avisado_at,r.pospuesto_hasta,!!postponed(r),r.can_finish].join('|'); }).join(';');
        if (!force && signature === pendingSignature) return;
        pendingSignature = signature;
        root.querySelector('[data-presence-pending]').innerHTML = due.map(function (r) {
            return '<article class="presence-card" data-presence-key="' + r.key + '"><h6 class="font-weight-bold">' + esc(r.estudiante) + ' <small>· Persona #' + r.codigo + '</small></h6><p class="small mb-1">' + esc(r.tipo) + ' #' + r.inscripcion_id + ' · Asistencia #' + r.id + ' · ' + esc(r.sucursal) + '<br>' + esc(r.docente) + ' · ' + esc(r.materia) + '<br>' + dateFormat.format(new Date(r.inicio)) + ' ' + timeFormat.format(new Date(r.inicio)) + ' – ' + timeFormat.format(new Date(r.fin)) + '</p><p class="small mb-2">Transcurrido: <span data-elapsed></span> · <strong data-remaining></strong></p>' + (r.avisado_at ? '<p class="small mb-2">Aviso registrado a las ' + timeFormat.format(new Date(r.avisado_at)) + '; la salida sigue pendiente.</p>' : '') + (postponed(r) ? '<p class="small mb-2">Pospuesto hasta ' + timeFormat.format(new Date(r.pospuesto_hasta)) + ' para tu usuario.</p>' : '') + actions(r, true) + '</article>';
        }).join('') || '<p class="mb-0">No hay salidas pendientes. Los avisos aparecerán al terminar cada horario.</p>';
        // One persistent banner and one modal: no repeated toasts or automatically stacked dialogs.
    }
    function tick() {
        if (stopped) return;
        renderPending(false);
        var map = new Map(rows.map(function (r) { return [r.key,r]; })), at = now();
        document.querySelectorAll('[data-presence-key]').forEach(function (node) {
            var r = map.get(node.dataset.presenceKey); if (!r) return;
            var color = state(r, at), remaining = (Date.parse(r.fin) - at) / 1000;
            ['normal','warning','due','overdue'].forEach(function (s) { node.classList.toggle('presence-' + s, color === s); });
            node.querySelector('[data-elapsed]').textContent = clock((at - Date.parse(r.inicio)) / 1000);
            node.querySelector('[data-remaining]').textContent = remaining > 0 ? clock(remaining) : 'Vencido ' + clock(-remaining);
            var label = node.querySelector('[data-presence-state]');
            if (label) label.textContent = {normal:'Dentro del horario',warning:'Próxima salida',due:'Hora de salida',overdue:'Salida pendiente vencida'}[color];
            node.querySelectorAll('button[data-presence-action]').forEach(function (button) { button.disabled = busy.has(r.key) || (button.dataset.presenceAction === 'notify' && !!r.avisado_at); });
        });
    }
    function error(message) {
        document.querySelectorAll('[data-presence-error],[data-presence-page-error]').forEach(function (n) { n.textContent = message; n.classList.toggle('d-none', !message); });
        if (message && !page && !jQuery(modal).hasClass('show')) {
            root.querySelector('.ife-exit-banner').classList.remove('d-none');
            root.querySelector('[data-presence-summary]').textContent = message;
        }
    }
    async function json(url, data, signal) {
        var response = await fetch(url, { method: data ? 'POST' : 'GET', credentials: 'same-origin', redirect: 'error', cache: 'no-store', signal: signal,
            headers: {Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':root.dataset.csrf,'Content-Type':'application/json'}, body: data ? JSON.stringify(data) : undefined });
        if ([401,403,419,423].includes(response.status)) {
            stopped = true; rows = []; renderPage(); renderPending(true);
            document.querySelectorAll('[data-presence-open],[data-presence-refresh]').forEach(function (b) { b.disabled = true; });
            throw new Error('La sesión o los permisos cambiaron. Vuelve a abrir el sistema para verificar tu acceso.');
        }
        if (response.status >= 500) throw new Error('No se pudo completar la operación. Reintenta o consulta con administración.');
        var result = await response.json();
        if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat()[0] : result.message || 'No se pudo completar la operación.');
        return result;
    }
    async function refresh() {
        clearTimeout(timer);
        if (stopped) return;
        if (inFlight) { queued = true; return; }
        var version = generation;
        var controller = new AbortController(); inFlight = controller;
        var timeout = setTimeout(function () { controller.abort(); }, 20000);
        try {
            var result = await json(root.dataset.statusUrl, null, controller.signal);
            if (version !== generation) return;
            rows = result.data; offset = Date.parse(result.server_now); anchor = performance.now(); failures = 0;
            renderPage(); renderPending(false); tick(); error('');
            if (page) page.querySelector('[data-presence-updated]').textContent = 'Actualizado a las ' + timeFormat.format(new Date(result.server_now)) + ' · America/La_Paz';
        } catch (e) {
            if (version === generation) { failures++; error(stopped ? e.message : 'No se pudo actualizar. Se reintentará automáticamente; los datos anteriores pueden estar desactualizados.'); }
        } finally {
            clearTimeout(timeout); inFlight = null;
            if (!stopped) timer = setTimeout(refresh, queued ? 0 : Math.min(120000, (document.hidden ? 60000 : 30000) * Math.max(1,failures)));
            queued = false;
        }
    }
    document.addEventListener('click', async function (event) {
        var open = event.target.closest('[data-presence-open]');
        if (open) {
            event.preventDefault();
            if (jQuery.fn.modal) jQuery(modal).modal('show');
            else if (window.bootstrap && window.bootstrap.Modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }
        if (event.target.closest('[data-presence-refresh]')) { refresh(); return; }
        var button = event.target.closest('[data-presence-action]');
        if (!button || button.disabled || stopped) return;
        var container = button.closest('[data-presence-key]'), key = container.dataset.presenceKey;
        var row = rows.find(function (r) { return r.key === key; });
        if (!row || busy.has(key)) return;
        var action = button.dataset.presenceAction;
        if (action === 'finish' && !window.confirm('¿Confirmas la salida de ' + row.estudiante + ' (asistencia #' + row.id + ')? Se registrará la fecha y hora actuales.')) return;
        busy.add(key); tick(); error('');
        try {
            await json(action === 'finish' ? row.finish_url : action === 'notify' ? row.notify_url : row.snooze_url,
                action === 'finish' ? {id:row.id} : action === 'snooze' ? {minutos:Number(container.querySelector('[data-snooze-minutes]').value)} : {});
            generation++;
            if (action === 'finish') rows = rows.filter(function (r) { return r.key !== key; });
            rows.forEach(function (r) {
                if (r.key !== key) return;
                if (action === 'notify') r.avisado_at = new Date(now()).toISOString();
                if (action === 'snooze') r.pospuesto_hasta = new Date(now() + Number(container.querySelector('[data-snooze-minutes]').value) * 60000).toISOString();
            });
            renderPage(); renderPending(true); tick();
            await refresh();
        } catch (e) { error(e.message); }
        finally { busy.delete(key); tick(); }
    });
    if (page) {
        document.getElementById('presence-search').addEventListener('input', function () { renderPage(); tick(); });
        document.getElementById('presence-channel').addEventListener('change', function () { renderPage(); tick(); });
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
    document.addEventListener('ife:attendance-changed', refresh);
    window.IFEPresentes = { refresh:refresh, state:state };
    setInterval(tick, 1000); refresh();
})();
