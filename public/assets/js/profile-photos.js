(function(){
    'use strict';
    if(window.IFEPhotos)return;
    const script=document.currentScript,base=script.dataset.photoBase,placeholder=script.dataset.photoPlaceholder;
    const decode=text=>String(text??'').replace(/&(amp|quot|apos|lt|gt|#x[0-9a-f]+|#[0-9]+);/gi,(all,key)=>{
        const named={amp:'&',quot:'"',apos:"'",lt:'<',gt:'>'};if(named[key])return named[key];
        const code=key[1].toLowerCase()==='x'?parseInt(key.slice(2),16):parseInt(key.slice(1),10);
        return code>0&&code<=0x10ffff?String.fromCodePoint(code):all;
    });
    function photoUrl(value){
        let name=decode(value).trim().replace(/\\/g,'/');
        if(!name||/^(sinfoto|sinperfil|foto|default|avatar|no[-_]?photo)\.(jpe?g|png|gif|webp)$/i.test(name.split('/').pop()))return placeholder;
        if(/^https?:\/\//i.test(name)){try{const url=new URL(name);return url.username||url.password?placeholder:url.href;}catch(_){return placeholder;}}
        if(/[:\x00-\x1f]|(^|\/)\.\.(\/|$)|^\/\//.test(name))return placeholder;
        name=name.replace(/^\/+/, '');
        return base+name.split('/').map(part=>encodeURIComponent(part)).join('/');
    }
    function render(value,type,row,options){
        if(type&&type!=='display')return '';
        options=options||{};row=row||{};
        const userPhoto=options.caption==='name';
        const img=document.createElement('img');img.src=photoUrl(userPhoto?(row.user_foto_url||value):(row.foto_url||value));img.dataset.profilePhoto='1';
        const requestedHeight=Number(options.height),height=Number.isFinite(requestedHeight)&&requestedHeight>0?requestedHeight:50;
        img.className='img-thumbnail ife-profile-photo';img.height=height;img.loading='lazy';img.decoding='async';
        img.style.setProperty('--ife-photo-height',height+'px');img.style.setProperty('--ife-photo-width',height*1.6+'px');
        img.alt=options.alt||(userPhoto?row.name:[row.nombre,row.apellidop,row.apellidom].filter(Boolean).join(' '))||row.name||'Fotografía';
        const code=userPhoto?row.foto_user_id:row.id;img.title=img.alt+(code?' · #'+code:'');
        if(!options.caption)return img.outerHTML;
        const box=document.createElement('div');box.className='contenedor';box.dataset.profilePhotoCell='1';const caption=document.createElement('div');
        caption.className=options.caption==='name'?'texto-encima':'codigo-encima';caption.textContent=String(row[options.caption]??'');
        box.append(img,caption);return box.outerHTML;
    }
    window.IFEPhotos={url:photoUrl,render};
    const zoomExcluded=img=>!!img.closest('.user-menu,[data-photo-zoom="off"]');
    let dialog,viewport,canvas,large,title,zoomLabel,plus,minus,previousFocus,originalOverflow;
    let width=0,height=0,fit=1,scale=1,request=0,panning=null,pinching=null;
    const pointers=new Map();
    function layout(anchor){
        if(!width||!height)return;
        const oldW=canvas.offsetWidth,oldH=canvas.offsetHeight;
        const focus=anchor||{x:viewport.clientWidth/2,y:viewport.clientHeight/2};
        const rx=(viewport.scrollLeft+focus.x)/Math.max(1,oldW),ry=(viewport.scrollTop+focus.y)/Math.max(1,oldH);
        const w=width*scale,h=height*scale,cw=Math.max(viewport.clientWidth,w),ch=Math.max(viewport.clientHeight,h);
        canvas.style.width=cw+'px';canvas.style.height=ch+'px';large.style.width=w+'px';large.style.height=h+'px';
        large.style.left=Math.max(0,(cw-w)/2)+'px';large.style.top=Math.max(0,(ch-h)/2)+'px';
        viewport.scrollLeft=rx*cw-focus.x;viewport.scrollTop=ry*ch-focus.y;
        zoomLabel.textContent=Math.round(scale*100)+'%';minus.disabled=scale<=fit+.001;plus.disabled=scale>=Math.max(6,fit);
    }
    function zoom(next,anchor){scale=Math.max(fit,Math.min(Math.max(6,fit),next));layout(anchor);}
    function reset(){if(!width)return;fit=Math.min(1,viewport.clientWidth/width,viewport.clientHeight/height);scale=fit;layout();viewport.scrollTo(0,0);}
    function close(){dialog.close();}
    function ensureDialog(){
        if(dialog)return;
        dialog=document.createElement('dialog');dialog.className='ife-photo-dialog';dialog.setAttribute('aria-labelledby','ife-photo-title');
        dialog.innerHTML='<header class="ife-photo-toolbar"><h2 id="ife-photo-title"></h2><div class="ife-photo-controls"><button type="button" data-photo-minus aria-label="Reducir fotografía">−</button><output class="ife-photo-zoom" aria-live="polite"></output><button type="button" data-photo-plus aria-label="Ampliar fotografía">+</button><button type="button" data-photo-fit>Ajustar</button><button type="button" data-photo-close>Cerrar</button></div></header><div class="ife-photo-viewport" tabindex="0" aria-label="Fotografía ampliada; arrastre para desplazar"><div class="ife-photo-canvas"><img alt="" draggable="false"></div><p class="ife-photo-error" hidden>No se pudo cargar la fotografía. Actualice la ficha o vuelva a subir el archivo.</p></div><p class="ife-photo-help">Use + y −, la rueda del ratón o dos dedos para ampliar. Arrastre para ver los detalles. Escape cierra la vista.</p>';
        document.body.append(dialog);viewport=dialog.querySelector('.ife-photo-viewport');canvas=dialog.querySelector('.ife-photo-canvas');
        large=canvas.querySelector('img');title=dialog.querySelector('h2');zoomLabel=dialog.querySelector('output');plus=dialog.querySelector('[data-photo-plus]');minus=dialog.querySelector('[data-photo-minus]');
        plus.onclick=()=>zoom(scale*1.25);minus.onclick=()=>zoom(scale/1.25);dialog.querySelector('[data-photo-fit]').onclick=reset;dialog.querySelector('[data-photo-close]').onclick=close;
        dialog.addEventListener('close',()=>{request++;pointers.clear();panning=null;pinching=null;document.documentElement.classList.remove('ife-photo-open');document.body.style.overflow=originalOverflow||'';if(previousFocus?.isConnected)previousFocus.focus({preventScroll:true});});
        dialog.addEventListener('click',event=>{if(event.target===dialog){const b=dialog.getBoundingClientRect();if(event.clientX<b.left||event.clientX>b.right||event.clientY<b.top||event.clientY>b.bottom)close();}});
        viewport.addEventListener('wheel',event=>{if(!width)return;event.preventDefault();const b=viewport.getBoundingClientRect();zoom(scale*(event.deltaY<0?1.15:1/1.15),{x:event.clientX-b.left,y:event.clientY-b.top});},{passive:false});
        viewport.addEventListener('keydown',event=>{if(event.key==='+'||event.key==='='){event.preventDefault();zoom(scale*1.25);}if(event.key==='-'){event.preventDefault();zoom(scale/1.25);}if(event.key==='0'){event.preventDefault();reset();}});
        viewport.addEventListener('pointerdown',event=>{if(event.button!==0||!width)return;pointers.set(event.pointerId,{x:event.clientX,y:event.clientY});viewport.setPointerCapture(event.pointerId);panning={x:event.clientX,y:event.clientY,left:viewport.scrollLeft,top:viewport.scrollTop};if(pointers.size===2){const [a,b]=[...pointers.values()];pinching={distance:Math.hypot(a.x-b.x,a.y-b.y),scale};panning=null;}});
        viewport.addEventListener('pointermove',event=>{if(!pointers.has(event.pointerId))return;pointers.set(event.pointerId,{x:event.clientX,y:event.clientY});if(pointers.size===2&&pinching){const [a,b]=[...pointers.values()],bounds=viewport.getBoundingClientRect();zoom(pinching.scale*Math.hypot(a.x-b.x,a.y-b.y)/Math.max(1,pinching.distance),{x:(a.x+b.x)/2-bounds.left,y:(a.y+b.y)/2-bounds.top});}else if(panning){viewport.scrollLeft=panning.left+panning.x-event.clientX;viewport.scrollTop=panning.top+panning.y-event.clientY;}});
        ['pointerup','pointercancel','lostpointercapture'].forEach(type=>viewport.addEventListener(type,event=>{pointers.delete(event.pointerId);pinching=null;panning=null;}));
        window.addEventListener('resize',()=>{if(dialog.open)reset();});
    }
    function openPhoto(img){
        if(zoomExcluded(img)||img.dataset.photoMissing==='1'||img.src===placeholder)return;
        ensureDialog();previousFocus=img;originalOverflow=document.body.style.overflow;
        title.textContent=img.title||img.alt||'Fotografía';large.alt=img.alt||'Fotografía ampliada';
        const seq=++request;width=0;height=0;zoomLabel.textContent='…';large.hidden=true;dialog.querySelector('.ife-photo-error').hidden=true;
        if(!dialog.open)dialog.showModal();document.documentElement.classList.add('ife-photo-open');document.body.style.overflow='hidden';
        large.onload=()=>{if(seq!==request||!dialog.open)return;width=large.naturalWidth;height=large.naturalHeight;large.hidden=false;reset();};
        large.onerror=()=>{if(seq!==request)return;dialog.querySelector('.ife-photo-error').hidden=false;zoomLabel.textContent='—';};
        large.src=img.dataset.photoFull||img.currentSrc||img.src;
    }
    function prepare(img){
        if(img.closest('.ife-photo-dialog'))return;
        let path;try{path=new URL(img.src,location.href).pathname;}catch(_){return;}
        if(!img.hasAttribute('data-profile-photo')&&!path.startsWith(new URL(base).pathname)&&img.src!==placeholder)return;
        if(img.dataset.photoSource!==img.src&&img.src!==placeholder)delete img.dataset.photoMissing;
        img.dataset.photoSource=img.src;
        img.dataset.profilePhoto='1';img.classList.remove('zoomify','zoomifyuser','materialboxed');
        if(!img.dataset.photoReady){img.dataset.photoReady='1';img.addEventListener('error',()=>{if(img.src===placeholder)return;img.dataset.photoMissing='1';img.src=placeholder;prepare(img);});}
        if(img.complete&&img.naturalWidth===0&&img.src!==placeholder){img.dataset.photoMissing='1';img.src=placeholder;prepare(img);return;}
        const missing=img.src===placeholder||img.dataset.photoMissing==='1';
        if(missing||zoomExcluded(img)){img.removeAttribute('role');img.removeAttribute('tabindex');img.removeAttribute('aria-label');if(missing)img.alt='Sin fotografía disponible';return;}
        img.setAttribute('role','button');img.tabIndex=0;img.setAttribute('aria-label','Ampliar fotografía: '+(img.alt||'perfil'));
    }
    function scan(scope){if(scope.matches?.('img'))prepare(scope);scope.querySelectorAll?.('img').forEach(prepare);}
    document.addEventListener('click',event=>{const img=event.target.closest?.('img[data-profile-photo]');if(!img||zoomExcluded(img)||img.dataset.photoMissing==='1'||img.src===placeholder)return;event.preventDefault();event.stopImmediatePropagation();openPhoto(img);},true);
    document.addEventListener('keydown',event=>{if(!['Enter',' '].includes(event.key)||!event.target.matches('img[data-profile-photo][role=button]')||zoomExcluded(event.target))return;event.preventDefault();event.stopImmediatePropagation();openPhoto(event.target);},true);
    function start(){scan(document);new MutationObserver(changes=>{changes.forEach(change=>{if(change.type==='attributes')prepare(change.target);else change.addedNodes.forEach(node=>{if(node.nodeType===1)scan(node);});});}).observe(document.body,{childList:true,subtree:true,attributes:true,attributeFilter:['src']});}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
