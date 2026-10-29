(function(){
    'use strict';
    const root=document.querySelector('[data-crm-root]'); if(!root)return;
    const notice=root.querySelector('[data-crm-status]');
    function status(message,bad){notice.textContent=message;notice.className='alert alert-'+(bad?'danger':'success');}
    async function json(url,options){const response=await fetch(url,Object.assign({credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}},options));let data;try{data=await response.json();}catch(_){throw new Error('No se pudo confirmar el resultado. Actualice la página.');}if(!response.ok)throw new Error(data.errors?Object.values(data.errors).flat().join(' '):data.message||'No se pudo guardar.');return data;}
    const board=root.querySelector('[data-board]'); let dragging=null;
    async function move(card,stage){
        if(card.dataset.busy||card.parentElement.closest('[data-stage]').dataset.stage===stage.dataset.stage)return;
        const origin=card.parentElement;const after=card.nextSibling;
        card.dataset.busy='1';card.setAttribute('aria-busy','true');stage.querySelector('.crm-cards').appendChild(card);
        try{
            const result=await json(card.dataset.move,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':board.dataset.token},body:JSON.stringify({etapa_id:Number(stage.dataset.stage),version:Number(card.dataset.version)})});
            card.dataset.version=result.version;card.querySelector('[data-stage-time]').textContent='En etapa: desde ahora';
            [origin.closest('[data-stage]'),stage].forEach((column,index)=>{const count=column.querySelector('[data-count]');count.textContent=Math.max(0,Number(count.textContent)+(index===0?-1:1));});
            status('Etapa guardada.',false);
        }catch(error){origin.insertBefore(card,after&&after.parentElement===origin?after:null);status(error.message,true);}
        finally{delete card.dataset.busy;card.removeAttribute('aria-busy');const select=card.querySelector('[data-change-stage]');if(select)select.value='';}
    }
    if(board){
        board.addEventListener('dragstart',event=>{const card=event.target.closest('[data-card][draggable=true]');if(!card||card.dataset.busy){event.preventDefault();return;}dragging=card;event.dataTransfer.setData('text/plain',card.dataset.card);event.dataTransfer.effectAllowed='move';});
        board.addEventListener('dragover',event=>{const stage=event.target.closest('[data-stage-active="1"]');if(stage&&dragging){event.preventDefault();event.dataTransfer.dropEffect='move';stage.classList.add('crm-drop-target');}});
        board.addEventListener('dragleave',event=>{const stage=event.target.closest('[data-stage]');if(stage&&!stage.contains(event.relatedTarget))stage.classList.remove('crm-drop-target');});
        board.addEventListener('drop',event=>{const stage=event.target.closest('[data-stage-active="1"]');if(stage&&dragging){event.preventDefault();move(dragging,stage);}board.querySelectorAll('.crm-drop-target').forEach(item=>item.classList.remove('crm-drop-target'));dragging=null;});
        board.addEventListener('dragend',()=>{dragging=null;board.querySelectorAll('.crm-drop-target').forEach(item=>item.classList.remove('crm-drop-target'));});
        board.addEventListener('change',event=>{if(!event.target.matches('[data-change-stage]')||!event.target.value)return;const stage=Array.from(board.querySelectorAll('[data-stage]')).find(item=>item.dataset.stage===event.target.value);if(stage)move(event.target.closest('[data-card]'),stage);});
        board.addEventListener('click',event=>{const card=event.target.closest('[data-card]');if(card&&!event.target.closest('a,button,select,input,label'))location.href=card.querySelector('.crm-card-title').href;});
    }
    const form=root.querySelector('[data-prospect-form]');
    if(form){let timer;let request=0;const phone=form.querySelector('[name=telefono]');const consent=form.querySelector('[name=confirmar_coincidencias]');
        async function lookup(){const seq=++request;const box=form.querySelector('[data-matches-box]');const list=form.querySelector('[data-matches-list]');if(!phone.value.trim()){box.classList.add('d-none');return;}try{const data=await json(form.dataset.matches+'?'+new URLSearchParams({telefono:phone.value,except:form.dataset.record||''}));if(seq!==request)return;list.replaceChildren();data.matches.forEach(item=>{const li=document.createElement('li');const link=document.createElement('a');link.href=item.url;link.target='_blank';link.rel='noopener';link.textContent=item.tipo+' #'+item.id+': '+item.nombre;li.appendChild(link);list.appendChild(li);});box.classList.toggle('d-none',!data.matches.length);}catch(error){status(error.message,true);}}
        phone.addEventListener('input',()=>{consent.checked=false;clearTimeout(timer);timer=setTimeout(lookup,400);});if(phone.value)lookup();
    }
    const wa=root.querySelector('[data-wa-dialog]');const open=root.querySelector('[data-open-wa]');
    if(wa&&open){let request=0;async function prepare(){const seq=++request;const link=wa.querySelector('[data-wa-link]');const error=wa.querySelector('[data-wa-error]');link.hidden=true;error.textContent='';try{const data=await json(open.dataset.url+'?'+new URLSearchParams({plantilla:wa.querySelector('[data-wa-template]').value}));if(seq!==request)return;wa.querySelector('[data-wa-text]').value=data.mensaje;link.href=data.url;link.hidden=false;}catch(e){error.textContent=e.message;}}
        open.addEventListener('click',()=>{wa.showModal();prepare();});wa.querySelector('[data-wa-template]').addEventListener('change',prepare);
    }
    root.querySelectorAll('[data-person-search]').forEach(container=>{
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
    });
})();
