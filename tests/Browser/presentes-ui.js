/* Synthetic fixture only. Every request is intercepted before the application script loads. */
(function () {
    'use strict';
    var fixture = window.presenceFixture, checks = [], requests = [], finishCalls = 0, allowExit = false, deny = false;
    var stalePoll = null, hold = false;
    function assert(condition, label) { if (!condition) throw new Error(label); checks.push(label); }
    function wait(ms) { return new Promise(function (resolve) { setTimeout(resolve, ms); }); }
    function response(status, data) { return { status:status, ok:status===200, json:async function () { return structuredClone(data); } }; }
    window.confirm = function () { return allowExit; };
    window.fetch = async function (url, options) {
        requests.push({url:url,options:options});
        if (deny) return response(403,{message:'Access revoked'});
        if (url.endsWith('/presentes/estado')) {
            if (hold) { hold = false; var snapshot=structuredClone(fixture); return new Promise(function (resolve) { stalePoll = function () { resolve(response(200,snapshot)); }; }); }
            return response(200,fixture);
        }
        if (options.method !== 'POST' || !options.headers['X-CSRF-TOKEN']) throw new Error('Unprotected mutation');
        var body = JSON.parse(options.body);
        if (url.includes('/avisar')) {
            var ack = url.match(/presentes\/(apoyo|computacion)\/(\d+)/);
            fixture.data.find(function (r) { return r.key===ack[1]+':'+ack[2]; }).avisado_at=fixture.server_now;
        } else if (url.includes('/posponer')) {
            var snooze = url.match(/presentes\/(apoyo|computacion)\/(\d+)/);
            fixture.data.find(function (r) { return r.key===snooze[1]+':'+snooze[2]; }).pospuesto_hasta=new Date(Date.parse(fixture.server_now)+body.minutos*60000).toISOString();
        } else if (url.includes('/finalizar')) {
            finishCalls++;
            var channel = url.includes('clasecom')?'computacion':'apoyo';
            fixture.data=fixture.data.filter(function (r) { return r.key!==channel+':'+body.id; });
        } else throw new Error('Unmocked request blocked: '+url);
        return response(200,{message:'OK'});
    };
    function report(ok, error) {
        var result={ok:ok,width:innerWidth,checks:checks,error:error||null};
        var node=document.createElement('pre');node.id='ui-test-result';node.textContent=JSON.stringify(result);document.body.append(node);
        if(window.parent!==window)window.parent.postMessage(result,'*');
    }
    if (location.search.includes('preview')) return;
    window.addEventListener('load',async function () {
        try {
            await wait(50);
            var page=document.getElementById('ife-presentes-list'), badge=document.querySelector('[data-presence-count]');
            if (!page) {
                assert(badge.textContent==='3'&&!document.querySelector('.ife-exit-banner').classList.contains('d-none'),'Global reminder visible on another module');
                document.querySelector('[data-presence-open]').click();await wait(300);
                assert(document.querySelector('#ife-exit-modal.show')&&document.querySelectorAll('article[data-presence-key]').length===3,'Grouped modal available outside presentes');
                document.querySelector('article[data-presence-key="apoyo:1"] [data-presence-action=notify]').click();await wait(80);
                assert(finishCalls===0&&fixture.data.length===5,'Global acknowledgement never marks exit');
                allowExit=true;document.querySelector('article[data-presence-key="apoyo:1"] [data-presence-action=finish]').click();await wait(100);
                assert(finishCalls===1&&badge.textContent==='2'&&document.querySelectorAll('#ife-exit-modal').length===1,'Global departure updates the single notification modal');
                report(true);return;
            }
            assert(page.querySelectorAll('[data-presence-key]').length===5,'Both circuits and previous-day attendance displayed');
            assert(badge.textContent==='3','Simultaneous departures grouped in one counter');
            assert(document.querySelectorAll('#ife-exit-modal').length===1&&!document.querySelector('#ife-exit-modal.show'),'One modal, no automatic stacked dialogs');
            var at=Date.parse(fixture.server_now), row={fin:fixture.server_now}, state=window.IFEPresentes.state;
            assert(state(row,at-601000)==='normal'&&state(row,at-600000)==='warning','Green and exact ten-minute orange thresholds');
            assert(state(row,at)==='due'&&state(row,at+60000)==='overdue','Light red arrival and dark red overdue thresholds');
            assert(page.querySelector('.presence-normal')&&page.querySelector('tr.presence-warning')&&page.querySelector('tr.presence-due')&&page.querySelector('tr.presence-overdue'),'All traffic light colors rendered');
            assert(page.textContent.includes('09:00 – 10:00'),'Bolivian hours rendered regardless of browser timezone');
            var before=requests.length, elapsed=page.querySelector('[data-elapsed]').textContent;
            await wait(2100);
            assert(page.querySelector('[data-elapsed]').textContent!==elapsed&&requests.length===before,'Timers tick without reloading or polling every second ('+elapsed+' -> '+page.querySelector('[data-elapsed]').textContent+'; requests '+before+'/'+requests.length+')');
            var originalEnd=fixture.data.find(function(r){return r.key==='apoyo:2';}).fin;
            fixture.data.find(function(r){return r.key==='apoyo:2';}).fin='2026-11-02T10:00:00.500-04:00';
            await IFEPresentes.refresh();await wait(2100);
            assert(badge.textContent==='4','New due class alerts automatically without another query');
            fixture.data.find(function(r){return r.key==='apoyo:2';}).fin=originalEnd;await IFEPresentes.refresh();
            document.querySelector('[data-presence-open]').click();await wait(300);
            assert(document.querySelector('#ife-exit-modal.show'),'Navbar opens grouped AdminLTE modal');
            var card=document.querySelector('article[data-presence-key="apoyo:1"]');
            card.querySelector('[data-presence-action=notify]').click();await wait(80);
            assert(finishCalls===0&&fixture.data.length===5&&document.querySelector('article[data-presence-key="apoyo:1"]').textContent.includes('Aviso registrado'),'Notify records acknowledgement without marking departure');
            document.querySelector('article[data-presence-key="computacion:1"] [data-presence-action=snooze]').click();await wait(80);
            assert(badge.textContent==='2'&&fixture.data.find(function(r){return r.key==='computacion:1';}).pospuesto_hasta,'Snooze persisted and removes active reminder temporarily');
            await IFEPresentes.refresh();assert(badge.textContent==='2','Snooze survives status refresh');
            fixture.server_now='2026-11-02T10:06:00-04:00';await IFEPresentes.refresh();
            assert(badge.textContent==='3','Snoozed reminder returns after expiry');
            var exit=document.querySelector('article[data-presence-key="apoyo:1"] [data-presence-action=finish]');
            exit.click();await wait(20);assert(finishCalls===0,'Cancelling confirmation sends no departure request');
            allowExit=true;exit.click();exit.click();await wait(100);
            assert(finishCalls===1&&!page.querySelector('[data-presence-key="apoyo:1"]'),'Confirmed double click records one exit and updates page immediately');
            assert(page.querySelector('[data-presence-key="computacion:1"]'),'Identical numeric id in other circuit remains present');
            hold=true;var oldPoll=IFEPresentes.refresh();await wait(20);
            document.querySelector('article[data-presence-key="computacion:1"] [data-presence-action=finish]').click();await wait(80);
            stalePoll();await oldPoll;await wait(80);
            assert(finishCalls===2&&!page.querySelector('[data-presence-key="computacion:1"]'),'An older in-flight poll cannot restore a finished attendance');
            document.getElementById('presence-channel').value='computacion';document.getElementById('presence-channel').dispatchEvent(new Event('change'));
            await IFEPresentes.refresh();assert(page.querySelectorAll('[data-presence-key]').length===0&&document.getElementById('presence-channel').value==='computacion','Channel filter survives background updates');
            document.getElementById('presence-channel').value='';document.getElementById('presence-channel').dispatchEvent(new Event('change'));
            assert(document.documentElement.scrollWidth<=innerWidth+1,'Responsive layout contains horizontal scrolling inside table');
            // A closed browser recovers persistent reminders by fetching the same server state.
            await IFEPresentes.refresh();assert(badge.textContent==='1','Pending departures recovered from server state');
            deny=true;await IFEPresentes.refresh();
            assert(!page.querySelector('[data-presence-key]')&&document.querySelector('[data-presence-page-error]').textContent.includes('permisos'),'Permission revocation clears student details and stops polling');
            report(true);
        } catch(e) { report(false,e.stack||String(e)); }
    });
})();
