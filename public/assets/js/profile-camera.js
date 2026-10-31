(function(){
    'use strict';
    const form=document.getElementById('formCanvas');if(!form)return;
    const video=document.querySelector('#contenedor video'),canvas=document.getElementById('canvas');
    const capture=document.getElementById('boton-tomar-foto'),save=document.getElementById('boton-guardar'),field=document.getElementById('imagen');
    const error=document.querySelector('[data-camera-error]');let stream;
    function unavailable(){document.getElementById('contenedor').classList.add('d-none');document.getElementById('sincamera').classList.remove('d-none');}
    capture.addEventListener('click',()=>{
        if(!video.videoWidth||!video.videoHeight){error.textContent='Espere a que la cámara muestre la imagen antes de tomar la foto.';return;}
        const ratio=Math.min(1,1600/video.videoWidth,1600/video.videoHeight);
        canvas.width=Math.round(video.videoWidth*ratio);canvas.height=Math.round(video.videoHeight*ratio);
        canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
        field.value=canvas.toDataURL('image/jpeg',.85);save.classList.remove('d-none');error.textContent='';
    });
    form.addEventListener('submit',event=>{if(!field.value){event.preventDefault();error.textContent='Tome la fotografía antes de guardarla.';}});
    if(!navigator.mediaDevices?.getUserMedia){unavailable();return;}
    navigator.mediaDevices.getUserMedia({audio:false,video:{width:{ideal:1280},height:{ideal:960}}}).then(result=>{stream=result;video.srcObject=result;}).catch(unavailable);
    window.addEventListener('pagehide',()=>stream?.getTracks().forEach(track=>track.stop()),{once:true});
})();
