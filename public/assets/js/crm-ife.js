(function(){
    'use strict';
    const root=document.querySelector('[data-crm-root]'); if(!root)return;
    const notice=root.querySelector('[data-crm-status]');
    function status(message,bad){notice.textContent=message;notice.className='alert alert-'+(bad?'danger':'success');}
    async function json(url,options){const response=await fetch(url,Object.assign({credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}},options));let data;try{data=await response.json();}catch(_){throw new Error('No se pudo confirmar el resultado. Actualice la página.');}if(!response.ok)throw new Error(data.errors?Object.values(data.errors).flat().join(' '):data.message||'No se pudo guardar.');return data;}
    const board=root.querySelector('[data-board]'); let dragging=null;
    try{
        if(board)sessionStorage.setItem('ifeCrmBoardUrl',location.pathname+location.search);
        const saved=sessionStorage.getItem('ifeCrmBoardUrl');
        if(saved)root.querySelectorAll('[data-return-board]').forEach(link=>{const url=new URL(saved,location.href);if(url.origin===location.origin&&url.pathname===new URL(link.href).pathname)link.href=url.href;});
    }catch(_){}
    if(board){
        const key='ifeCrmBoard:'+location.pathname+location.search;
        try{const saved=Number(sessionStorage.getItem(key)||0);requestAnimationFrame(()=>{board.scrollLeft=saved;});}catch(_){}
        board.addEventListener('scroll',()=>{try{sessionStorage.setItem(key,String(board.scrollLeft));}catch(_){}},{passive:true});
        root.querySelectorAll('[data-board-scroll]').forEach(button=>button.addEventListener('click',()=>board.scrollBy({left:Number(button.dataset.boardScroll)*Math.max(280,board.clientWidth*.8),behavior:'smooth'})));
        board.addEventListener('dragover',event=>{if(!dragging)return;const bounds=board.getBoundingClientRect();if(event.clientX<bounds.left+55)board.scrollLeft-=30;else if(event.clientX>bounds.right-55)board.scrollLeft+=30;});
    }
    async function move(card,stage){
        if(card.dataset.busy||card.parentElement.closest('[data-stage]').dataset.stage===stage.dataset.stage)return;
        if(stage.dataset.stageConversion==='1'){await openConversion(card,stage);const choice=card.querySelector('[data-change-stage]');if(choice)choice.value='';return;}
        const origin=card.parentElement;const after=card.nextSibling;
        const scroll=board.scrollLeft;
        card.dataset.busy='1';card.setAttribute('aria-busy','true');stage.querySelector('.crm-cards').appendChild(card);board.scrollLeft=scroll;
        try{
            const result=await json(card.dataset.move,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':board.dataset.token},body:JSON.stringify({etapa_id:Number(stage.dataset.stage),version:Number(card.dataset.version)})});
            card.dataset.version=result.version;card.querySelector('[data-stage-time]').textContent='En etapa: desde ahora';
            [origin.closest('[data-stage]'),stage].forEach((column,index)=>{const count=column.querySelector('[data-count]');count.textContent=Math.max(0,Number(count.textContent)+(index===0?-1:1));});
            status('Etapa guardada.',false);
        }catch(error){origin.insertBefore(card,after&&after.parentElement===origin?after:null);status(error.message,true);}
        finally{delete card.dataset.busy;card.removeAttribute('aria-busy');const select=card.querySelector('[data-change-stage]');if(select)select.value='';}
    }
    if(board){
        board.addEventListener('dragstart',event=>{const card=event.target.closest('[data-card][draggable=true]');if(!card||card.dataset.busy||event.target.closest('select,input,button')){event.preventDefault();return;}dragging=card;event.dataTransfer.setData('text/plain',card.dataset.card);event.dataTransfer.effectAllowed='move';});
        board.addEventListener('dragover',event=>{const stage=event.target.closest('[data-stage-active="1"]');if(stage&&dragging&&(stage.dataset.stageConversion!=='1'||dragging.dataset.conversionUrl)){event.preventDefault();event.dataTransfer.dropEffect='move';stage.classList.add('crm-drop-target');}});
        board.addEventListener('dragleave',event=>{const stage=event.target.closest('[data-stage]');if(stage&&!stage.contains(event.relatedTarget))stage.classList.remove('crm-drop-target');});
        board.addEventListener('drop',event=>{const stage=event.target.closest('[data-stage-active="1"]');if(stage&&dragging){event.preventDefault();move(dragging,stage);}board.querySelectorAll('.crm-drop-target').forEach(item=>item.classList.remove('crm-drop-target'));dragging=null;});
        board.addEventListener('dragend',()=>{dragging=null;board.querySelectorAll('.crm-drop-target').forEach(item=>item.classList.remove('crm-drop-target'));});
        board.addEventListener('change',event=>{if(!event.target.matches('[data-change-stage]')||!event.target.value)return;const stage=Array.from(board.querySelectorAll('[data-stage]')).find(item=>item.dataset.stage===event.target.value);if(stage)move(event.target.closest('[data-card]'),stage);});
        board.addEventListener('click',event=>{const card=event.target.closest('[data-card]');if(card&&!event.target.closest('a,button,select,input,label'))location.href=card.querySelector('.crm-card-title').href;});
    }
    root.querySelectorAll('[data-filter-select]').forEach(input=>{
        const select=document.getElementById(input.dataset.filterSelect);if(!select)return;
        const normalize=text=>text.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase();
        input.addEventListener('input',()=>{const query=normalize(input.value.trim());Array.from(select.options).forEach(option=>{option.hidden=!!option.value&&!option.selected&&!normalize(option.textContent).includes(query);});});
    });
    const form=root.querySelector('[data-prospect-form]');
    if(form){let timer;let request=0;const phone=form.querySelector('[name=telefono]');const consent=form.querySelector('[name=confirmar_coincidencias]');
        async function lookup(){const seq=++request;const box=form.querySelector('[data-matches-box]');const list=form.querySelector('[data-matches-list]');if(!phone.value.trim()){box.classList.add('d-none');return;}try{const data=await json(form.dataset.matches+'?'+new URLSearchParams({telefono:phone.value,except:form.dataset.record||''}));if(seq!==request)return;list.replaceChildren();data.matches.forEach(item=>{const li=document.createElement('li');const link=document.createElement(item.url?'a':'span');if(item.url){link.href=item.url;link.target='_blank';link.rel='noopener';}link.textContent=item.tipo+' #'+item.id+': '+item.nombre;li.appendChild(link);list.appendChild(li);});box.classList.toggle('d-none',!data.matches.length);}catch(error){status(error.message,true);}}
        phone.addEventListener('input',()=>{consent.checked=false;clearTimeout(timer);timer=setTimeout(lookup,400);});if(phone.value)lookup();
    }
    const wa=root.querySelector('[data-wa-dialog]');const open=root.querySelector('[data-open-wa]');
    if(wa&&open){let request=0;async function prepare(){const seq=++request;const link=wa.querySelector('[data-wa-link]');const error=wa.querySelector('[data-wa-error]');link.hidden=true;error.textContent='';try{const data=await json(open.dataset.url+'?'+new URLSearchParams({plantilla:wa.querySelector('[data-wa-template]').value}));if(seq!==request)return;wa.querySelector('[data-wa-text]').value=data.mensaje;link.href=data.url;link.hidden=false;}catch(e){error.textContent=e.message;}}
        open.addEventListener('click',()=>{wa.showModal();prepare();});wa.querySelector('[data-wa-template]').addEventListener('change',prepare);
    }
    function initializePeople(scope){scope.querySelectorAll('[data-person-search]').forEach(container=>{
        if(container.dataset.ready)return;container.dataset.ready='1';
        let timer;let request=0;
        const input=container.querySelector('[data-person-query]');const select=container.querySelector('[data-person-select]');
        function links(){const option=select.selectedOptions[0];container.querySelectorAll('[data-person-link]').forEach(link=>{const url=option&&option.dataset[link.dataset.personLink];link.hidden=!url;if(url)link.href=url;});}
        select.addEventListener('change',links);
        input.addEventListener('input',()=>{
            clearTimeout(timer);const seq=++request;
            if(input.value.trim().length<3&&!/^\d+$/.test(input.value.trim()))return;
            timer=setTimeout(async()=>{
                try{
                    const data=await json(container.dataset.url+'?'+new URLSearchParams({q:input.value}));if(seq!==request)return;
                    const selected=select.selectedOptions[0];const keep=selected&&selected.value?selected.cloneNode(true):null;
                    select.replaceChildren(new Option('Seleccione una persona',''));if(keep)select.appendChild(keep);
                    data.people.filter(person=>!keep||String(person.id)!==keep.value).forEach(person=>{
                        const option=new Option(person.nombre+' (#'+person.id+')',person.id);
                        ['url','academico','computacion'].forEach(key=>{if(person[key])option.dataset[key]=person[key];});
                        select.appendChild(option);
                    });
                    if(keep)select.value=keep.value;links();
                }catch(e){status(e.message,true);}
            },350);
        });
    });}
    const conversionDialog=root.querySelector('[data-conversion-dialog]');let conversionRequest=0;
    async function openConversion(card,stage){
        if(!conversionDialog||!card.dataset.conversionUrl){status('No tiene permiso para confirmar esta conversión.',true);return;}
        const sequence=++conversionRequest;const body=conversionDialog.querySelector('[data-conversion-body]');
        body.replaceChildren();const loading=document.createElement('p');loading.textContent='Cargando la inscripción existente…';body.appendChild(loading);
        if(!conversionDialog.open)conversionDialog.showModal();
        try{
            const url=new URL(card.dataset.conversionUrl,location.href);if(stage)url.searchParams.set('etapa_id',stage.dataset.stage);
            const response=await fetch(url,{credentials:'same-origin',cache:'no-store',headers:{Accept:'text/html'}});
            if(!response.ok)throw new Error('No se pudo abrir la conversión. Actualice el tablero y revise sus permisos.');
            const html=await response.text();if(sequence!==conversionRequest||!conversionDialog.open)return;
            body.innerHTML=html;
            if(!body.querySelector('[data-crm-conversion-form]'))throw new Error('La sesión pudo terminar. Actualice el sistema y vuelva a ingresar.');
            initializePeople(body);initializeConversions(body);
        }catch(error){if(sequence===conversionRequest){body.replaceChildren();const message=document.createElement('p');message.className='alert alert-danger';message.textContent=error.message;body.appendChild(message);}}
    }
    if(conversionDialog){
        conversionDialog.querySelector('[data-close-conversion]').addEventListener('click',()=>{if(!conversionDialog.dataset.busy){conversionRequest++;conversionDialog.close();}});
        conversionDialog.addEventListener('cancel',event=>{if(conversionDialog.dataset.busy)event.preventDefault();else conversionRequest++;});
        root.addEventListener('click',event=>{const button=event.target.closest('[data-start-conversion]');if(!button)return;const card=button.closest('[data-card]');const stage=board.querySelector('[data-stage-conversion="1"][data-stage-active="1"]');if(!stage){status('Configure una etapa activa de conversión.',true);return;}if(!card.dataset.busy)openConversion(card,stage);});
    }
    function initializeConversions(scope){scope.querySelectorAll('[data-crm-conversion-form]').forEach(form=>{
        if(form.dataset.ready)return;form.dataset.ready='1';let request=0;
        const contact=form.querySelector('[name=persona_id]'),student=form.querySelector('[name=estudiante_persona_id]'),picker=form.querySelector('[data-conversion-enrollment]');
        const type=form.querySelector('[name=tipo]'),id=form.querySelector('[name=inscripcion_id]'),confirmation=form.querySelector('[name=confirmar_vinculos]'),help=form.querySelector('[data-conversion-help]');
        const error=form.querySelector('[data-conversion-error]'),googleChoice=form.querySelector('[data-google-link-choice]'),keepGoogle=form.querySelector('[name=conservar_contactos_google]');
        function googleConflict(conflict){if(!googleChoice)return;googleChoice.hidden=!conflict;keepGoogle.required=conflict;if(!conflict)keepGoogle.checked=false;}
        async function enrollments(clear){
            const sequence=++request;if(clear){id.value='';confirmation.checked=false;googleConflict(false);}
            picker.replaceChildren(new Option(contact.value?'Cargando inscripciones…':'Seleccione primero al contacto',''));
            if(!contact.value){googleConflict(false);return;}
            try{
                const data=await json(form.dataset.enrollmentsUrl+'?'+new URLSearchParams({persona_id:contact.value,estudiante_persona_id:student.value}));if(sequence!==request)return;
                googleConflict(data.google_conflict===true);
                picker.replaceChildren(new Option('Seleccione la inscripción ya registrada',''));
                data.students.forEach(person=>{if(!Array.from(student.options).some(option=>option.value===String(person.id)))student.add(new Option(person.nombre+' (#'+person.id+')',person.id));});
                data.enrollments.forEach(item=>{const option=new Option(item.label+(item.tiene_pago===true?' · Pago registrado':''),item.tipo+':'+item.id);option.dataset.type=item.tipo;option.dataset.enrollment=String(item.id);option.dataset.student=String(item.estudiante_persona_id);picker.add(option);});
                help.textContent=data.enrollments.length?'Seleccione la inscripción correcta para completar el estudiante y el código.':'No hay inscripciones para esta selección. Busque al estudiante correcto o complete su inscripción por el proceso habitual.';
            }catch(e){picker.replaceChildren(new Option('No se pudieron cargar las inscripciones',''));help.textContent=e.message;if(googleChoice)googleChoice.hidden=false;}
        }
        contact.addEventListener('change',()=>{student.value='';enrollments(true);});student.addEventListener('change',()=>enrollments(true));
        picker.addEventListener('change',()=>{const option=picker.selectedOptions[0];confirmation.checked=false;if(!option||!option.value){id.value='';return;}type.value=option.dataset.type;id.value=option.dataset.enrollment;student.value=option.dataset.student;});
        [type,id].forEach(input=>input.addEventListener('input',()=>{confirmation.checked=false;}));enrollments(false);
        if(!form.closest('[data-conversion-dialog]'))return;
        form.addEventListener('submit',async event=>{
            event.preventDefault();if(form.dataset.busy)return;
            form.dataset.busy='1';conversionDialog.dataset.busy='1';const submit=form.querySelector('[data-confirm-conversion]');submit.disabled=true;error.classList.add('d-none');
            try{
                const result=await json(form.action,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':form.querySelector('[name=_token]').value},body:new FormData(form)});
                if(!result.converted)throw new Error('No se confirmó la conversión. Actualice el tablero.');
                const url=new URL(location.href);url.searchParams.set('status','operativo');url.searchParams.delete('crm_etapa_id');url.searchParams.delete('agenda');
                location.href=url.href;
            }catch(e){error.textContent=e.message;error.classList.remove('d-none');if(googleChoice)googleChoice.hidden=false;}
            finally{delete form.dataset.busy;delete conversionDialog.dataset.busy;submit.disabled=false;}
        });
    });}
    initializePeople(root);initializeConversions(root);
})();
