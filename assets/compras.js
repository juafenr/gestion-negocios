(function () {
    'use strict';
    const tabla = document.getElementById('lineas-compra');
    const agregar = document.getElementById('agregar-linea');
    if (tabla && agregar) {
        const plantilla = tabla.rows[0].cloneNode(true);
        plantilla.querySelector('select').value = '';
        plantilla.querySelector('input').value = '';
        function actualizar() {
            tabla.querySelectorAll('.quitar-linea').forEach(b => { b.hidden = false; });
            agregar.disabled = tabla.rows.length >= 100;
        }
        agregar.hidden = false;
        agregar.addEventListener('click', () => {
            if (tabla.rows.length >= 100) return;
            const fila = plantilla.cloneNode(true); tabla.appendChild(fila); actualizar(); fila.querySelector('select').focus();
        });
        tabla.addEventListener('click', evento => {
            const boton = evento.target.closest('.quitar-linea'); if (!boton) return;
            if (tabla.rows.length > 1) boton.closest('tr').remove();
            else { tabla.rows[0].querySelector('select').value = ''; tabla.rows[0].querySelector('input').value = ''; }
            actualizar();
        });
        actualizar();
    }
    const copiar = document.getElementById('copiar-documento');
    if (copiar) {
        copiar.hidden = false;
        copiar.addEventListener('click', async () => {
            const campo = document.getElementById('documento-compra');
            const estado = document.getElementById('resultado-copia');
            try {
                if (!navigator.clipboard) throw new Error('Sin portapapeles');
                await navigator.clipboard.writeText(campo.value); estado.textContent = 'Texto copiado.';
            } catch (_) {
                campo.focus(); campo.select(); estado.textContent = 'Texto seleccionado. Presiona Ctrl+C o utiliza la opción Copiar de tu dispositivo.';
            }
        });
    }
}());
