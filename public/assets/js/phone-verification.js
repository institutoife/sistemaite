(function () {
    'use strict';
    const dialog = document.getElementById('ife-phone-verification');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = '1';
    const field = selector => dialog.querySelector(selector);
    const endpoint = id => dialog.dataset.endpoint.replace('/0/telefono-verificacion', '/' + id + '/telefono-verificacion');
    const seenRoots = new Set();
    const seenPeople = new Set();
    const waiting = [];
    let current = null;
    let busy = false;
    let searchTimer;

    function error(message) {
        field('[data-phone-error]').textContent = message;
        field('[data-phone-error]').hidden = !message;
    }
    function next() {
        current = waiting.shift() || null;
        if (!current) { if (dialog.open) dialog.close(); return; }
        field('[data-phone-name]').textContent = current.name;
        field('[data-phone-context]').textContent = current.relationship + ' · Código ' + current.id;
        field('[data-phone-current]').textContent = current.phone || 'Sin teléfono registrado';
        field('[data-phone-question]').textContent = current.valid_phone
            ? '¿El cliente sigue utilizando este número?' : 'Solicite al cliente un número de contacto válido.';
        field('[data-phone-action="confirm"]').hidden = !current.valid_phone;
        field('[data-phone-action="reload"]').hidden = true;
        field('#ife-phone-input').value = '';
        error('');
        if (!dialog.open) dialog.showModal();
        (current.valid_phone ? field('[data-phone-action="confirm"]') : field('#ife-phone-input')).focus();
    }
    async function review(id, manual) {
        id = Number(id);
        if (!Number.isSafeInteger(id) || id < 1 || (!manual && seenRoots.has(id))) return;
        seenRoots.add(id);
        try {
            const response = await fetch(endpoint(id) + (manual ? '?review_now=1' : ''), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store'
            });
            if (!response.ok) { seenRoots.delete(id); return; }
            const payload = await response.json();
            (payload.people || []).forEach(person => {
                if (current && current.id === person.id || waiting.some(item => item.id === person.id)) return;
                if (!manual && seenPeople.has(person.id)) return;
                waiting.push(person);
            });
            if (!current) next();
        } catch (_) { seenRoots.delete(id); }
    }
    async function save(action) {
        if (!current || busy) return;
        const person = current;
        busy = true;
        dialog.querySelectorAll('button').forEach(button => { button.disabled = true; });
        error('');
        try {
            const response = await fetch(endpoint(person.id), {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dialog.dataset.token },
                body: JSON.stringify({ action: action, version: person.version, phone: field('#ife-phone-input').value })
            });
            const payload = await response.json();
            if (!response.ok) {
                error(payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.message || 'No se pudo guardar. Intente nuevamente.');
                field('[data-phone-action="reload"]').hidden = response.status !== 409;
                return;
            }
            seenPeople.add(person.id);
            if (action === 'update') {
                document.querySelectorAll('[data-phone-persona="' + person.id + '"]').forEach(element => {
                    element.textContent = payload.phone;
                    if (element.tagName === 'A') element.href = 'tel:' + payload.phone;
                });
            }
            next();
        } catch (_) { error('No se pudo guardar la confirmación. Revise la conexión y vuelva a intentarlo.'); }
        finally {
            busy = false;
            dialog.querySelectorAll('button').forEach(button => { button.disabled = false; });
        }
    }
    field('[data-phone-form]').addEventListener('submit', event => { event.preventDefault(); save('update'); });
    dialog.addEventListener('click', event => {
        const button = event.target.closest('[data-phone-action]');
        if (!button || busy) return;
        const action = button.dataset.phoneAction;
        if (action === 'reload') {
            const id = current.id;
            seenPeople.delete(id);
            current = null;
            waiting.length = 0;
            dialog.close();
            review(id, true);
        } else if (action === 'close') {
            save('defer');
        } else { save(action); }
    });
    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        if (busy) return;
        // Escape always permits leaving the screen, even if the connection is unavailable.
        if (current) {
            fetch(endpoint(current.id), { method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dialog.dataset.token },
                body: JSON.stringify({ action: 'defer', version: current.version }) }).catch(() => {});
        }
        waiting.length = 0;
        current = null;
        dialog.close();
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-verify-person]');
        if (button) { event.preventDefault(); review(button.dataset.verifyPerson, true); }
    });
    if (window.jQuery) {
        const $ = window.jQuery;
        $(document).on('xhr.dt.phoneVerification', function (_, settings, payload) {
            clearTimeout(searchTimer);
            const query = settings.oPreviousSearch && settings.oPreviousSearch.sSearch || '';
            if (settings.sTableId !== 'personas' || query.trim().length < 3 || !payload || Number(payload.recordsFiltered) !== 1 || !payload.data || !payload.data[0]) return;
            searchTimer = setTimeout(() => review(payload.data[0].id, false), 900);
        });
        $(document).ajaxSuccess(function (_, xhr, options) {
            const path = new URL(options.url, window.location.href).pathname;
            const paths = ['/persona/mostrar', '/persona/mostrar/ajax', '/persona/mostrar/ajax/matriculacion', '/persona/enviar/mensaje/componente'];
            if (paths.some(suffix => path.endsWith(suffix)) && xhr.responseJSON && xhr.responseJSON.persona) {
                review(xhr.responseJSON.persona.id, false);
            }
        });
    }
    if (Number(dialog.dataset.root)) review(dialog.dataset.root, false);
})();
