document.addEventListener('DOMContentLoaded', () => {
    const inputBusqueda = document.getElementById('input-busqueda');
    const tarjetas = document.querySelectorAll('.tarjeta-mentor');
    const contador = document.getElementById('contador-mentores');

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

//forms    const formRegistro = document.getElementById('form-registro');
    if (formRegistro) {
        const radiosRol = formRegistro.querySelectorAll('[data-rol-radio]');
        const labelNivel = document.getElementById('label-nivel');
        const bloqueExperiencia = document.getElementById('bloque-experiencia');
        const selectModalidad = document.getElementById('reg-modalidad');
        const bloqueUbicacion = document.getElementById('bloque-ubicacion');
        const selectNivel = document.getElementById('reg-nivel');
        const inputNivelOtro = document.getElementById('reg-nivel-otro');

        const actualizarNivelOtro = () => {
            if (!selectNivel || !inputNivelOtro) return;
            const esOtro = selectNivel.value === 'Otro';
            inputNivelOtro.classList.toggle('hidden', !esOtro);
            inputNivelOtro.required = esOtro;
            if (esOtro) { inputNivelOtro.focus(); }
        };

        const actualizarPorRol = () => {
            const rol = (formRegistro.querySelector('[data-rol-radio]:checked') || {}).value || 'estudiante';
            const esMentor = rol === 'mentor' || rol === 'ambos';
            if (labelNivel) {
                labelNivel.textContent = esMentor ? 'Nivel máximo que enseñas *' : 'Grado que cursas *';
            }
            if (bloqueExperiencia) {
                bloqueExperiencia.classList.toggle('hidden', !esMentor);
            }
        };
        const actualizarPorModalidad = () => {
            if (bloqueUbicacion && selectModalidad) {
                bloqueUbicacion.classList.toggle('hidden', selectModalidad.value === 'Virtual');
            }
        };

        radiosRol.forEach((r) => r.addEventListener('change', actualizarPorRol));
        if (selectModalidad) {
            selectModalidad.addEventListener('change', actualizarPorModalidad);
        }
        if (selectNivel) {
            selectNivel.addEventListener('change', actualizarNivelOtro);
        }
        actualizarPorRol();
        actualizarPorModalidad();
        if (inputNivelOtro) {
            // Al cargar (p. ej. tras un error) solo se muestra; no se enfoca.
            const esOtro = selectNivel && selectNivel.value === 'Otro';
            inputNivelOtro.classList.toggle('hidden', !esOtro);
            inputNivelOtro.required = !!esOtro;
        }
    }
 //password
    document.querySelectorAll('[data-toggle-password]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const input = document.getElementById(boton.getAttribute('data-toggle-password'));
            if (!input) return;
            const mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';
            boton.textContent = mostrar ? 'Ocultar' : 'Mostrar';
        });
    });

    const selectDisp = document.getElementById('select-disponibilidad');
    const inputFecha = document.getElementById('input-fecha-sesion');
    if (selectDisp && inputFecha) {
        const indiceDia = { 'Domingo': 0, 'Lunes': 1, 'Martes': 2, 'Miércoles': 3, 'Jueves': 4, 'Viernes': 5, 'Sábado': 6 };
        const aplicarProximaFecha = () => {
            const opcion = selectDisp.options[selectDisp.selectedIndex];
            const dia = opcion ? indiceDia[opcion.getAttribute('data-dia')] : undefined;
            if (dia === undefined) return;
            const fecha = new Date();
            fecha.setHours(12, 0, 0, 0);
            // Empieza a partir de mañana para no proponer horarios que ya pasaron hoy.
            do { fecha.setDate(fecha.getDate() + 1); } while (fecha.getDay() !== dia);
            const pad = (n) => String(n).padStart(2, '0');
            inputFecha.value = `${fecha.getFullYear()}-${pad(fecha.getMonth() + 1)}-${pad(fecha.getDate())}`;
        };
        selectDisp.addEventListener('change', aplicarProximaFecha);
    }
});
