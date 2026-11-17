function autocompletarDemo(correo, password) {
    const inputCorreo = document.getElementById('login-correo');
    const inputPassword = document.getElementById('login-password');
    if (inputCorreo && inputPassword) {
        inputCorreo.value = correo;
        inputPassword.value = password;
        inputCorreo.focus();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const inputBusqueda = document.getElementById('input-busqueda');
    const inputArea = document.getElementById('input-area');
    const selectMateria = document.getElementById('select-materia');
    const tarjetas = document.querySelectorAll('.tarjeta-mentor');
    const contador = document.getElementById('contador-mentores');

    if(inputArea&&selectMateria){
        const areaActiva=inputArea.value.trim();
        if (areaActiva!=='') {
            Array.from(selectMateria.options).forEach((opt)=>{
                const optArea=opt.getAttribute('data-area')||'';
                if(opt.value!==''&&optArea!==areaActiva){
                    opt.hidden=true;
                }
            });
        }
    }

    if(inputBusqueda&&tarjetas.length>0){
        inputBusqueda.addEventListener('input',()=>{
            const termino=inputBusqueda.value.trim().toLowerCase();
            let visibles=0;

            tarjetas.forEach((card)=>{
                const contenido=(card.getAttribute('data-search') || '').toLowerCase();
                if (termino===''||contenido.includes(termino)){
                    card.style.display='';
                    visibles++;
                }else{
                    card.style.display='none';
                }
            });

            if(contador){
                contador.textContent=`${visibles} disponibles`;
            }
        });
    }
});
