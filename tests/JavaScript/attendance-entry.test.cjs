const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '../..');

function attendanceForm(duration = 90) {
    const handlers = [], intervals = [];
    const document = { addEventListener() {} };
    const input = value => ({ value, validity: '', setCustomValidity(value) { this.validity = value; } });
    const fields = { horainicio: input('14:20'), horafin: input(''), fecha: input('2026-11-02') };
    const panel = { attrs: { 'data-server-now': '2026-11-02T10:00:00-04:00' }, store: {} };
    const form = { attrs: { 'data-duration': String(duration) }, store: {}, fields, panel };
    Object.values(fields).forEach(field => { field.form = form; });
    const scheduled = { form, attrs: { 'data-start': '09:00', 'data-end': '10:30' } };
    function wrap(value) {
        return {
            0: value, length: value ? 1 : 0,
            closest(selector) { return wrap(selector === 'form' ? value.form : value.panel); },
            data(key, val) { if (val === undefined) return value.store[key]; value.store[key] = val; return this; },
            attr(key, val) { if (val === undefined) return value.attrs[key]; value.attrs[key] = val; return this; },
            find(selector) { return wrap(value.fields?.[selector.match(/^\[name=(.+)\]$/)?.[1]]); },
            val(val) { if (val === undefined) return value.value; value.value = val; return this; },
            hasClass() { return false; },
            each(callback) { if (value) callback.call(value); return this; },
        };
    }
    function $(value) {
        if (value === document) return { on(events, selector, callback) { handlers.push({ events, selector, callback }); return this; } };
        if (typeof value === 'function') return;
        if (value === '.attendance-entry-form:visible') return wrap(form);
        return wrap(value);
    }
    const window = { setInterval(callback, delay) { intervals.push({ callback, delay }); }, performance: { now: () => 0 } };
    vm.runInNewContext(fs.readFileSync(path.join(root, 'public/vistas/clase/asistencia.js'), 'utf8'), { jQuery: $, document, window, Intl });
    function event(selector, field) {
        const handler = handlers.find(handler => handler.selector === selector);
        assert.ok(handler, selector);
        handler.callback.call(field);
    }
    return { fields, form, scheduled, event, intervals };
}

test('real entry recalculates each individual duration and does not silently wrap midnight', () => {
    const selector = '.attendance-add-form [name=horainicio], .attendance-entry-form [name=horainicio]';
    for (const [duration, expected] of [[60, '15:20'], [90, '15:50']]) {
        const fixture = attendanceForm(duration);
        fixture.event(selector, fixture.fields.horainicio);
        assert.equal(fixture.fields.horafin.value, expected);
        assert.equal(fixture.form.store['time-edited'], true);
    }
    const fixture = attendanceForm();
    fixture.fields.horainicio.value = '23:30';
    fixture.event(selector, fixture.fields.horainicio);
    assert.equal(fixture.fields.horafin.value, '');
    assert.match(fixture.fields.horafin.validity, /mismo día/);
});

test('the clock uses Bolivia time, respects manual adjustments and scheduled-hours only fills fields', () => {
    const fixture = attendanceForm();
    const clock = fixture.intervals.find(interval => interval.delay === 15000);
    clock.callback();
    assert.equal(fixture.fields.horainicio.value, '10:00');
    assert.equal(fixture.fields.horafin.value, '11:30');
    fixture.event('.attendance-scheduled', fixture.scheduled);
    assert.equal(fixture.fields.horainicio.value, '09:00');
    assert.equal(fixture.fields.horafin.value, '10:30');
    clock.callback();
    assert.equal(fixture.fields.horainicio.value, '09:00');
    fixture.fields.horafin.value = '10:10';
    fixture.event('.attendance-entry-form [name=horafin]', fixture.fields.horafin);
    assert.equal(fixture.fields.horafin.validity, '');
    fixture.fields.horafin.value = '11:00';
    fixture.event('.attendance-entry-form [name=horafin]', fixture.fields.horafin);
    assert.match(fixture.fields.horafin.validity, /duración/);
});

function noticePage(flash = null, storageDisabled = false) {
    const elements = [], timers = new Map(), storage = new Map();
    const document = {
        readyState: 'complete',
        body: { appendChild(element) { elements.push(element); } },
        getElementById(id) { return elements.find(element => element.id === id); },
        querySelector() { return flash ? { getAttribute() { return JSON.stringify(flash); } } : null; },
        createElement(tag) {
            return { tag, children: [], attributes: {}, listeners: {},
                setAttribute(key, value) { this.attributes[key] = value; },
                addEventListener(event, callback) { this.listeners[event] = callback; },
                append(...children) { this.children.push(...children); },
                remove() { const index = elements.indexOf(this); if (index >= 0) elements.splice(index, 1); },
            };
        },
    };
    let next = 0;
    const window = {
        setTimeout(callback, delay) { timers.set(++next, { callback, delay }); return next; },
        clearTimeout(id) { timers.delete(id); },
    };
    const sessionStorage = {
        getItem(key) { if (storageDisabled) throw new Error('disabled'); return storage.get(key); },
        setItem(key, value) { storage.set(key, value); }, removeItem(key) { storage.delete(key); },
    };
    vm.runInNewContext(fs.readFileSync(path.join(root, 'public/vistas/clase/arrival-notice.js'), 'utf8'), { window, document, sessionStorage });
    return { api: window.IFEArrivalNotice, elements, timers, storage };
}

test('arrival toast is nonblocking, safe text, dismissible, lasts eight seconds and never stacks', () => {
    const fixture = noticePage();
    fixture.api.show({ type: 'early', title: 'Llegada anticipada', message: '<img src=x onerror=alert(1)>' });
    let toast = fixture.elements[0];
    assert.equal(toast.attributes.role, 'status');
    assert.equal(toast.children[1].textContent, '<img src=x onerror=alert(1)>');
    assert.ok(!('innerHTML' in toast.children[1]));
    assert.equal([...fixture.timers.values()][0].delay, 8000);
    fixture.api.show({ type: 'late', title: 'Llegada tardía', message: 'Horario inscrito: 09:00–10:30. Entrada real: 10:00. Diferencia: 60 minutos después.' });
    assert.equal(fixture.elements.length, 1);
    assert.equal(fixture.timers.size, 1);
    toast = fixture.elements[0];
    assert.match(toast.className, /is-late/);
    toast.children[2].listeners.click();
    assert.equal(fixture.elements.length, 0);
    assert.equal(fixture.timers.size, 0);
    fixture.api.show({ title: 'Aviso', message: 'Temporal' });
    [...fixture.timers.values()][0].callback();
    assert.equal(fixture.elements.length, 0);
});

test('quick-entry server flash works even when browser storage is disabled', () => {
    const notice = { type: 'late', title: 'Llegada tardía', message: 'Entrada real: 10:00' };
    const fixture = noticePage(notice, true);
    assert.equal(fixture.elements[0].children[1].textContent, notice.message);
    const normal = noticePage();
    normal.api.save(notice);
    assert.equal(JSON.parse(normal.storage.get('ife-attendance-arrival')).type, 'late');
});
